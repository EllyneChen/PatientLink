<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ConsentRecord;
use App\Models\Patient;
use App\Models\Doctor;
use App\Models\AuditLog;
use App\Services\SmsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

/**
 * ConsentController
 * 
 * Manages the full OTP consent lifecycle:
 *
 * FR-D03  Doctor requests consent access
 * FR-P02  Patient receives OTP via SMS
 * FR-P03  Patient approves consent with OTP
 * FR-P04  Patient rejects consent request
 * FR-P05  Patient views consent history
 */
class ConsentController extends Controller
{
    private SmsService $sms;

    public function __construct(SmsService $sms)
    {
        $this->sms = $sms;
    }

    /**
     * FR-D03: Doctor submits a consent access request.
     * Generates a 6-digit OTP, hashes it, stores the consent
     * record, and sends the OTP to the patient via SMS.
     */
    public function requestAccess(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'patient_nupi' => 'required|string|exists:patients,nupi',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $doctor = $request->user()->doctor;

        if (!$doctor) {
            return response()->json(['error' => 'Doctor profile not found'], 404);
        }

        // Check for existing pending request
        $existing = ConsentRecord::where('patient_nupi', $request->patient_nupi)
            ->where('doctor_id', $doctor->id)
            ->where('status', 'pending')
            ->where('expires_at', '>', now())
            ->first();

        if ($existing) {
            return response()->json([
                'message' => 'A pending consent request already exists for this patient.',
                'consent_id' => $existing->id,
                'expires_at' => $existing->expires_at,
            ], 409);
        }

        // Get patient phone number
        $patient = Patient::where('nupi', $request->patient_nupi)->first();

        // Generate 6-digit OTP
        $otpCode = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $expiryMinutes = config('services.africastalking.otp_expiry', 5);

        // Create consent record
        $consent = ConsentRecord::create([
            'patient_nupi' => $request->patient_nupi,
            'doctor_id'    => $doctor->id,
            'facility_id'  => $doctor->facility_id,
            'otp_hash'     => Hash::make($otpCode),
            'status'       => 'pending',
            'expires_at'   => Carbon::now()->addMinutes($expiryMinutes),
        ]);

        // Send OTP via SMS (FR-S01)
        $smsSent = $this->sms->sendOtp($patient->phone, $otpCode);

        // Log the request
        AuditLog::record(
            $request->user()->id,
            'CONSENT_REQUEST',
            'success',
            'ConsentRecord',
            $consent->id,
            ['patient_nupi' => $request->patient_nupi, 'sms_sent' => $smsSent]
        );

        return response()->json([
            'message'    => 'Consent request created. OTP sent to patient\'s registered phone.',
            'consent_id' => $consent->id,
            'expires_at' => $consent->expires_at,
            'sms_sent'   => $smsSent,
            // Only show OTP in sandbox/local mode for testing
            'otp_code'   => app()->environment('local') ? $otpCode : null,
        ], 201);
    }

    /**
     * FR-P03: Patient approves a consent request by submitting OTP.
     */
    public function approve(Request $request, string $consentId)
    {
        $validator = Validator::make($request->all(), [
            'otp_code' => 'required|string|size:6',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $patient = $request->user()->patient;

        if (!$patient) {
            return response()->json(['error' => 'Patient profile not found'], 404);
        }

        $consent = ConsentRecord::where('id', $consentId)
            ->where('patient_nupi', $patient->nupi)
            ->where('status', 'pending')
            ->first();

        if (!$consent) {
            return response()->json(['error' => 'Consent request not found or already resolved'], 404);
        }

        // Verify OTP (also handles expiry check)
        if (!$consent->verifyOtp($request->otp_code)) {
            AuditLog::record(
                $request->user()->id,
                'CONSENT_OTP_FAILED',
                'denied',
                'ConsentRecord',
                $consent->id
            );

            if ($consent->status === 'expired') {
                return response()->json(['error' => 'OTP has expired. Please ask the doctor to send a new request.'], 410);
            }

            return response()->json(['error' => 'Invalid OTP code. Please try again.'], 401);
        }

        // Approve the consent
        $consent->update([
            'status'      => 'approved',
            'resolved_at' => now(),
        ]);

        AuditLog::record(
            $request->user()->id,
            'CONSENT_APPROVED',
            'success',
            'ConsentRecord',
            $consent->id,
            ['patient_nupi' => $patient->nupi]
        );

        return response()->json([
            'message'    => 'Consent approved successfully. The doctor can now access your health records.',
            'consent_id' => $consent->id,
            'status'     => 'approved',
        ]);
    }

    /**
     * FR-P04: Patient rejects a consent request.
     */
    public function reject(Request $request, string $consentId)
    {
        $patient = $request->user()->patient;

        if (!$patient) {
            return response()->json(['error' => 'Patient profile not found'], 404);
        }

        $consent = ConsentRecord::where('id', $consentId)
            ->where('patient_nupi', $patient->nupi)
            ->where('status', 'pending')
            ->first();

        if (!$consent) {
            return response()->json(['error' => 'Consent request not found or already resolved'], 404);
        }

        $consent->update([
            'status'      => 'rejected',
            'resolved_at' => now(),
        ]);

        AuditLog::record(
            $request->user()->id,
            'CONSENT_REJECTED',
            'denied',
            'ConsentRecord',
            $consent->id,
            ['patient_nupi' => $patient->nupi]
        );

        return response()->json([
            'message'    => 'Consent request rejected.',
            'consent_id' => $consent->id,
            'status'     => 'rejected',
        ]);
    }

    /**
     * FR-P05: Patient views their full consent history.
     */
    public function history(Request $request)
    {
        $patient = $request->user()->patient;

        if (!$patient) {
            return response()->json(['error' => 'Patient profile not found'], 404);
        }

        $history = ConsentRecord::where('patient_nupi', $patient->nupi)
            ->with(['doctor.user', 'facility'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($record) {
                return [
                    'consent_id'    => $record->id,
                    'doctor_name'   => $record->doctor->user->name,
                    'doctor_licence'=> $record->doctor->licence_no,
                    'facility'      => $record->facility->name,
                    'status'        => $record->status,
                    'requested_at'  => $record->created_at,
                    'resolved_at'   => $record->resolved_at,
                    'expires_at'    => $record->expires_at,
                ];
            });

        return response()->json([
            'patient_nupi' => $patient->nupi,
            'total'        => $history->count(),
            'history'      => $history,
        ]);
    }

    /**
     * Patient views pending consent requests.
     */
    public function pendingRequests(Request $request)
    {
        $patient = $request->user()->patient;

        if (!$patient) {
            return response()->json(['error' => 'Patient profile not found'], 404);
        }

        $pending = ConsentRecord::where('patient_nupi', $patient->nupi)
            ->where('status', 'pending')
            ->where('expires_at', '>', now())
            ->with(['doctor.user', 'facility'])
            ->get()
            ->map(function ($record) {
                return [
                    'consent_id'     => $record->id,
                    'doctor_name'    => $record->doctor->user->name,
                    'doctor_licence' => $record->doctor->licence_no,
                    'facility'       => $record->facility->name,
                    'expires_at'     => $record->expires_at,
                    'minutes_left'   => now()->diffInMinutes($record->expires_at),
                ];
            });

        return response()->json([
            'pending_requests' => $pending,
            'total'            => $pending->count(),
        ]);
    }
}
