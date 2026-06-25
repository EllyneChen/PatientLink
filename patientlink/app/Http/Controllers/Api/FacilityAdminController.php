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

    public function listStaff()
    {
        [$user, $admin] = $this->getAdmin();
        $staff = Doctor::where('facility_id', $admin->facility_id)
            ->with('user:id,name,email,is_active')
            ->get()
            ->map(fn($d) => [
                'doctor_id'      => $d->id,
                'user_id'        => $d->user_id,
                'name'           => $d->user->name,
                'email'          => $d->user->email,
                'licence_no'     => $d->licence_no,
                'specialisation' => $d->specialisation,
                'phone'          => $d->phone,
                'is_active'      => $d->user->is_active,
            ]);
        return response()->json(['staff' => $staff]);
    }

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
            'id'        => (string) Str::uuid(),
            'name'      => $request->name,
            'email'     => $request->email,
            'password'  => Hash::make($request->password),
            'role'      => 'doctor',
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
        AuditLog::record($user->id, 'STAFF_CREATED', 'success', null, null, ['email' => $newUser->email]);
        return response()->json(['message' => 'Doctor account created successfully'], 201);
    }

    public function deactivateStaff(Request $request, string $staffId)
    {
        [$user, $admin] = $this->getAdmin();
        $doctor = Doctor::where('id', $staffId)
            ->where('facility_id', $admin->facility_id)
            ->firstOrFail();
        User::findOrFail($doctor->user_id)->update(['is_active' => false]);
        AuditLog::record($user->id, 'STAFF_DEACTIVATED', 'success', null, null, ['target' => $doctor->user_id]);
        return response()->json(['message' => 'Staff account deactivated']);
    }

    public function activateStaff(Request $request, string $staffId)
    {
        [$user, $admin] = $this->getAdmin();
        $doctor = Doctor::where('id', $staffId)
            ->where('facility_id', $admin->facility_id)
            ->firstOrFail();
        User::findOrFail($doctor->user_id)->update(['is_active' => true]);
        AuditLog::record($user->id, 'STAFF_ACTIVATED', 'success', null, null, ['target' => $doctor->user_id]);
        return response()->json(['message' => 'Staff account activated']);
    }
    public function deleteStaff(Request $request, string $staffId)
{
    [$user, $admin] = $this->getAdmin();
    $doctor = Doctor::where('id', $staffId)
        ->where('facility_id', $admin->facility_id)
        ->firstOrFail();
    $targetUser = User::findOrFail($doctor->user_id);
    $doctor->delete();
    $targetUser->delete();
    AuditLog::record($user->id, 'STAFF_DELETED', 'success', null, null, ['target' => $staffId]);
    return response()->json(['message' => 'Staff account deleted']);
}
}