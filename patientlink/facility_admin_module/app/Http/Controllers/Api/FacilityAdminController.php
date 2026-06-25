<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Doctor;
use App\Models\FacilityAdmin;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;

class FacilityAdminController extends Controller
{
    private function getAdmin()
    {
        $user = JWTAuth::parseToken()->authenticate();
        $admin = FacilityAdmin::where('user_id', $user->id)->firstOrFail();
        return [$user, $admin];
    }

    /**
     * GET /api/facility-admin/staff
     * List all doctors at this facility (FR-FA03)
     */
    public function listStaff(Request $request)
    {
        [$user, $admin] = $this->getAdmin();

        $staff = Doctor::where('facility_id', $admin->facility_id)
            ->with('user:id,name,email,is_active')
            ->get()
            ->map(fn($d) => [
                'doctor_id'      => $d->id,
                'name'           => $d->user->name,
                'email'          => $d->user->email,
                'licence_no'     => $d->licence_no,
                'specialisation' => $d->specialisation,
                'phone'          => $d->phone,
                'is_active'      => $d->user->is_active,
            ]);

        return response()->json(['staff' => $staff]);
    }

    /**
     * POST /api/facility-admin/staff
     * Create a new doctor account at this facility (FR-FA03)
     */
    public function createStaff(Request $request)
    {
        [$user, $admin] = $this->getAdmin();

        $validator = Validator::make($request->all(), [
            'name'           => 'required|string|max:255',
            'email'          => 'required|email|unique:users,email',
            'password'       => 'required|string|min:8',
            'licence_no'     => 'required|string|unique:doctors,licence_no',
            'specialisation' => 'nullable|string',
            'phone'          => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $newUser = User::create([
            'id'       => (string) Str::uuid(),
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
            'role'     => 'doctor',
            'is_active' => true,
        ]);

        Doctor::create([
            'id'             => (string) Str::uuid(),
            'user_id'        => $newUser->id,
            'facility_id'    => $admin->facility_id,
            'licence_no'     => $request->licence_no,
            'specialisation' => $request->specialisation,
            'phone'          => $request->phone,
        ]);

        AuditLog::record($user->id, 'STAFF_CREATED', 'success', null, null, [
            'new_user_id' => $newUser->id,
            'email'       => $newUser->email,
        ]);

        return response()->json(['message' => 'Doctor account created successfully', 'user_id' => $newUser->id], 201);
    }

    /**
     * PUT /api/facility-admin/staff/{userId}/deactivate
     * Deactivate a staff account (FR-FA03)
     */
    public function deactivateStaff(Request $request, string $userId)
    {
        [$user, $admin] = $this->getAdmin();

        // Ensure the target doctor belongs to this facility
        $doctor = Doctor::where('user_id', $userId)
            ->where('facility_id', $admin->facility_id)
            ->firstOrFail();

        $targetUser = User::findOrFail($userId);
        $targetUser->update(['is_active' => false]);

        AuditLog::record($user->id, 'STAFF_DEACTIVATED', 'success', null, null, [
            'target_user_id' => $userId,
        ]);

        return response()->json(['message' => 'Staff account deactivated']);
    }

    /**
     * PUT /api/facility-admin/staff/{userId}/activate
     * Reactivate a staff account
     */
    public function activateStaff(Request $request, string $userId)
    {
        [$user, $admin] = $this->getAdmin();

        $doctor = Doctor::where('user_id', $userId)
            ->where('facility_id', $admin->facility_id)
            ->firstOrFail();

        $targetUser = User::findOrFail($userId);
        $targetUser->update(['is_active' => true]);

        AuditLog::record($user->id, 'STAFF_ACTIVATED', 'success', null, null, [
            'target_user_id' => $userId,
        ]);

        return response()->json(['message' => 'Staff account activated']);
    }
}
