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
            'nupi'  => $patient->nupi,
            'name'  => $patient->user->name,
            'dob'   => $patient->dob,
            'phone' => $patient->phone,
        ]);
    }

    public function requestConsent(Request $request)
    {
        $request->validate(['nupi' => 'required|string']);

        $user    = JWTAuth::parseToken()->authenticate();
        $doctor  = Doctor::where('user_id', $user->id)->firstOrFail();
        $patient = Patient::where('nupi', $request->nupi)->firstOrFail();

        ConsentRecord::where('patient_nupi', $request->nupi)
            ->where('doctor_id', $doctor->id)
            ->where('status', 'pending')
            ->update(['status' => 'expired']);

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

       try {
    $AT = new \AfricasTalking\SDK\AfricasTalking(
        env('AT_USERNAME'),
        env('AT_API_KEY')
    );
    $AT->sms()->send([
        'to'      => '+' . $patient->phone,
        'message' => "Your PatientLink OTP is {$otp}. Valid for 10 minutes. Do not share this code.",
    ]);
} catch (\Exception $smsEx) {
    Log::warning("SMS failed: " . $smsEx->getMessage());
}

        AuditLog::record($user->id, 'CONSENT_REQUESTED', 'success', null, null, [
            'nupi'       => $request->nupi,
            'consent_id' => $consent->id,
        ]);

        return response()->json([
            'message'    => 'OTP sent to patient phone (simulated)',
            'consent_id' => $consent->id,
            'otp_demo'   => $otp,
        ]);
    }

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
    public function addRecord(Request $request, string $nupi)
{
    $request->validate([
        'diagnosis' => 'required|string',
        'allergies' => 'nullable|string',
        'previous_doctor' => 'nullable|string',
        'facility' => 'nullable|string',
        'date' => 'nullable|date',
        'clinical_notes' => 'nullable|string',
    ]);

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

    $record = HealthRecord::create([
        'id'           => (string) \Illuminate\Support\Str::uuid(),
        'patient_nupi' => $nupi,
        'facility_id'  => $doctor->facility_id,
        'doctor_id'    => $doctor->id,
        'summary'      => json_encode([
            'diagnosis'       => $request->diagnosis,
            'allergies'       => $request->allergies,
            'previous_doctor' => $request->previous_doctor,
            'facility'        => $request->facility,
            'date'            => $request->date,
            'clinical_notes'  => $request->clinical_notes,
        ]),
        'encrypted' => false,
    ]);

    AuditLog::record($user->id, 'RECORD_ADDED', 'success', null, null, [
        'nupi'      => $nupi,
        'record_id' => $record->id,
    ]);

    return response()->json(['message' => 'Record added successfully'], 201);
}

public function exportRecordsPdf(Request $request, string $nupi)
{
    $user   = JWTAuth::parseToken()->authenticate();
    $doctor = Doctor::where('user_id', $user->id)->firstOrFail();

    $records = HealthRecord::where('patient_nupi', $nupi)
        ->where('doctor_id', $doctor->id)
        ->orderBy('created_at', 'desc')
        ->get()
        ->map(function($r) {
            $summary = is_string($r->summary) ? json_decode($r->summary, true) : $r->summary;
            return [
                'date'           => $r->created_at ? $r->created_at->format('d M Y') : '-',
                'diagnosis'      => $summary['diagnosis'] ?? '-',
                'allergies'      => $summary['allergies'] ?? '-',
                'facility'       => $summary['facility'] ?? '-',
                'clinical_notes' => $summary['clinical_notes'] ?? '-',
            ];
        });

    $patient = \App\Models\Patient::where('nupi', $nupi)->firstOrFail();

    $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('reports.doctor-records', [
        'records'     => $records,
        'patientName' => $patient->name ?? $nupi,
        'doctorName'  => $user->name,
        'generatedAt' => now()->format('d M Y, H:i'),
    ]);

    return $pdf->download('patient-records-' . $nupi . '.pdf');
}
public function exportSingleRecordPdf(Request $request, string $nupi, string $recordId)
{
    $user   = JWTAuth::parseToken()->authenticate();
    $doctor = Doctor::where('user_id', $user->id)->firstOrFail();

    $record = HealthRecord::where('id', $recordId)
        ->where('patient_nupi', $nupi)
        ->where('doctor_id', $doctor->id)
        ->firstOrFail();

    $summary = is_string($record->summary) ? json_decode($record->summary, true) : $record->summary;
    $patient = Patient::where('nupi', $nupi)->with('user:id,name')->firstOrFail();

    $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('reports.doctor-single-record', [
        'record' => [
            'date'           => $record->created_at ? $record->created_at->format('d M Y') : '-',
            'diagnosis'      => $summary['diagnosis'] ?? '-',
            'allergies'      => $summary['allergies'] ?? '-',
            'facility'       => $summary['facility'] ?? '-',
            'clinical_notes' => $summary['clinical_notes'] ?? '-',
        ],
        'patientName' => $patient->user->name ?? $nupi,
        'patientNupi' => $nupi,
        'doctorName'  => $user->name,
        'generatedAt' => now()->format('d M Y, H:i'),
    ]);

    return $pdf->download('record-' . $nupi . '-' . $record->created_at->format('Y-m-d') . '.pdf');
}
}