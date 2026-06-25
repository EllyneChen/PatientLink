<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Patient;
use App\Models\Doctor;
use App\Models\FacilityAdmin;
use App\Models\MohAdmin;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\Facades\JWTAuth;

/**
 * AuthController
 * ----------------
 * Implements role-scoped login for all four PatientLink actors:
 *   FR-D01  Doctor login          -> JWT scoped to "clinical access"
 *   FR-P01  Patient login         -> JWT scoped to "patient portal access"
 *   FR-FA01 Facility Admin login  -> JWT scoped to "staff management access"
 *   FR-M01  MOH Admin login       -> JWT scoped to "analytics access"
 *
 * The "scope" referenced in the requirements is implemented as the
 * `role` custom claim embedded in the JWT (see User::getJWTCustomClaims).
 * RoleMiddleware then restricts each route group to the relevant role(s).
 */
class AuthController extends Controller
{
    /**
     * Register a new user. In production this would normally be
     * restricted (e.g. only Facility Admins can create Doctor accounts,
     * only MOH can create Facility Admins), but for prototype/testing
     * purposes this endpoint allows creating any role.
     *
     * Each role gets its corresponding extension row created in the
     * same DB transaction (patients, doctors, facility_admins, moh_admins).
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'role'     => 'required|in:doctor,patient,facility_admin,moh_admin',

            // Patient-specific fields
            'nupi'              => 'required_if:role,patient|string|unique:patients,nupi',
            'dob'               => 'required_if:role,patient|date',
            'phone'             => 'required_if:role,patient,doctor|string',

            // Doctor-specific fields
            'facility_id'       => 'required_if:role,doctor,facility_admin|exists:facilities,id',
            'licence_no'        => 'required_if:role,doctor|string|unique:doctors,licence_no',
            'specialisation'    => 'nullable|string',

            // MOH Admin fields
            'region'            => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = DB::transaction(function () use ($request) {
            $user = User::create([
                'name'     => $request->name,
                'email'    => $request->email,
                'password' => Hash::make($request->password),
                'role'     => $request->role,
            ]);

            switch ($request->role) {
                case 'patient':
                    Patient::create([
                        'user_id' => $user->id,
                        'nupi'    => $request->nupi,
                        'dob'     => $request->dob,
                        'phone'   => $request->phone,
                        'next_of_kin_name'  => $request->next_of_kin_name,
                        'next_of_kin_phone' => $request->next_of_kin_phone,
                    ]);
                    break;

                case 'doctor':
                    Doctor::create([
                        'user_id'        => $user->id,
                        'facility_id'    => $request->facility_id,
                        'licence_no'     => $request->licence_no,
                        'specialisation' => $request->specialisation,
                        'phone'          => $request->phone,
                    ]);
                    break;

                case 'facility_admin':
                    FacilityAdmin::create([
                        'user_id'     => $user->id,
                        'facility_id' => $request->facility_id,
                        'admin_level' => $request->admin_level ?? 'standard',
                    ]);
                    break;

                case 'moh_admin':
                    MohAdmin::create([
                        'user_id' => $user->id,
                        'region'  => $request->region,
                        'clearance_level' => $request->clearance_level ?? 1,
                    ]);
                    break;
            }

            return $user;
        });

        AuditLog::record($user->id, 'REGISTER', 'success', 'User', $user->id, ['role' => $user->role]);

        return response()->json([
            'message' => 'User registered successfully',
            'user'    => $user,
        ], 201);
    }

    /**
     * Login — issues a role-scoped JWT.
     * Implements FR-D01, FR-P01, FR-FA01, FR-M01.
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $credentials = $request->only('email', 'password');

        $user = User::where('email', $credentials['email'])->first();

        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            if ($user) {
                AuditLog::record($user->id, 'LOGIN', 'failure');
            }
            return response()->json(['error' => 'Invalid credentials'], 401);
        }

        if (!$user->is_active) {
            AuditLog::record($user->id, 'LOGIN', 'denied', null, null, ['reason' => 'account_inactive']);
            return response()->json(['error' => 'Account is inactive. Contact your facility administrator.'], 403);
        }

        if (!$token = JWTAuth::fromUser($user)) {
            return response()->json(['error' => 'Could not create token'], 500);
        }

        AuditLog::record($user->id, 'LOGIN', 'success');

        return response()->json([
            'access_token' => $token,
            'token_type'   => 'bearer',
            'expires_in'   => auth('api')->factory()->getTTL() * 60,
            'role'         => $user->role, // the "scope" of the token
            'user'         => [
                'id'    => $user->id,
                'name'  => $user->name,
                'email' => $user->email,
                'role'  => $user->role,
            ],
        ]);
    }

    /**
     * Return the currently authenticated user (any role).
     */
    public function me(Request $request)
    {
        return response()->json($request->user());
    }

    /**
     * Logout — invalidates the current JWT.
     */
    public function logout(Request $request)
    {
        $user = $request->user();
        JWTAuth::invalidate(JWTAuth::getToken());

        AuditLog::record($user->id, 'LOGOUT', 'success');

        return response()->json(['message' => 'Successfully logged out']);
    }

    /**
     * Refresh an expiring JWT.
     */
    public function refresh()
    {
        return response()->json([
            'access_token' => JWTAuth::refresh(JWTAuth::getToken()),
            'token_type'   => 'bearer',
            'expires_in'   => auth('api')->factory()->getTTL() * 60,
        ]);
    }
}
