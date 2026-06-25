<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Patient;
use App\Models\Doctor;
use App\Models\ConsentRecord;
use App\Models\HealthRecord;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Tymon\JWTAuth\Facades\JWTAuth;

class DoctorController extends Controller
{
    /**
     * Search patient by NUPI (FR-D02)
     */
    public function searchPatient(Request $request)
    {
        $request->validate(['nupi' => 'required|string']);

        $patient = Patient::where('nupi', $request->nupi)
            ->with('user:id,name,email')
            ->first();

        if (!$patient) {
            return response()->json(['error' => 'Patient not found'], 404);
        }

        $user = JWTAuth::parseToken()->authenticate();

        AuditLog::record($user->id, 'PATIENT_SEARCH', 'success', null, null, [
            'nupi' => $request->nupi,
        ]);

        return response()->json([
            'nupi'       => $patient->nupi,
            'name'       => $patient->user->name,
            'dob'        => $patient->dob,
            'phone'      => $patient->phone,
        ]);
    }

    /**
     * Request consent — generates OTP, logs it (simulated SMS) (FR-D03)
     */
    public function requestConsent(Request $request)
    {
        $request->validate(['nupi' => 'required|string']);

        $user    = JWTAuth::parseToken()->authenticate();
        $doctor  = Doctor::where('user_id', $user->id)->firstOrFail();
        $patient = Patient::where('nupi', $request->nupi)->firstOrFail();

        // Expire any previous pending consent for this doctor+patient
        ConsentRecord::where('patient_nupi', $request->nupi)
            ->where('doctor_id', $doctor->id)
            ->where('status', 'pending')
            ->update(['status' => 'expired']);

        // Generate 6-digit OTP
        $otp     = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $otpHash = Hash::make($otp);

        $consent = ConsentRecord::create([
            'id'           => (string) Str::uuid(),
            'patient_nupi' => $request->nupi,
            'doctor_id'    => $doctor->id,
            'facility_id'  => $doctor->facility_id,
            'otp_hash'     => $otpHash,
            'status'       => 'pending',
            'expires_at'   => now()->addMinutes(10),
        ]);

        // Simulate SMS — in production replace with Africa's Talking call
        Log::info("📱 SIMULATED SMS to patient {$patient->phone}: Your PatientLink OTP is {$otp}. Valid for 10 minutes.");

        AuditLog::record($user->id, 'CONSENT_REQUESTED', 'success', null, null, [
            'nupi'       => $request->nupi,
            'consent_id' => $consent->id,
        ]);

        return response()->json([
            'message'    => 'OTP sent to patient\'s phone (simulated — check Laravel log)',
            'consent_id' => $consent->id,
            // REMOVE in production — only here for demo/testing
            'otp_demo'   => $otp,
        ]);
    }

    /**
     * Verify OTP and grant access (FR-D03)
     */
    public function verifyConsent(Request $request)
    {
        $request->validate([
            'consent_id' => 'required|string',
            'otp'        => 'required|string',
        ]);

        $consent = ConsentRecord::where('id', $request->consent_id)
            ->where('status', 'pending')
            ->first();

        if (!$consent) {
            return response()->json(['error' => 'Consent request not found or already resolved'], 404);
        }

        if (now()->isAfter($consent->expires_at)) {
            $consent->update(['status' => 'expired']);
            return response()->json(['error' => 'OTP has expired'], 410);
        }

        if (!Hash::check($request->otp, $consent->otp_hash)) {
            return response()->json(['error' => 'Invalid OTP'], 401);
        }

        $consent->update([
            'status'      => 'approved',
            'resolved_at' => now(),
        ]);

        $user = JWTAuth::parseToken()->authenticate();
        AuditLog::record($user->id, 'CONSENT_APPROVED', 'success', null, null, [
            'consent_id' => $consent->id,
            'nupi'       => $consent->patient_nupi,
        ]);

        return response()->json(['message' => 'Access granted', 'nupi' => $consent->patient_nupi]);
    }

    /**
     * View patient health records (requires approved consent) (FR-D04)
     */
    public function viewRecords(Request $request, string $nupi)
    {
        $user   = JWTAuth::parseToken()->authenticate();
        $doctor = Doctor::where('user_id', $user->id)->firstOrFail();

        $consent = ConsentRecord::where('patient_nupi', $nupi)
            ->where('doctor_id', $doctor->id)
            ->where('status', 'approved')
            ->where('expires_at', '>', now())
            ->first();

        if (!$consent) {
            return response()->json(['error' => 'No active consent for this patient'], 403);
        }

        $records = HealthRecord::where('patient_nupi', $nupi)->get();
        $patient = Patient::where('nupi', $nupi)->with('user:id,name')->first();

        AuditLog::record($user->id, 'RECORDS_VIEWED', 'success', null, null, [
            'nupi' => $nupi,
        ]);

        return response()->json([
            'patient' => [
                'nupi' => $nupi,
                'name' => $patient->user->name,
                'dob'  => $patient->dob,
            ],
            'records' => $records,
        ]);
    }
}
