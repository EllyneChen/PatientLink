<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

/**
 * PatientController
 * ------------------
 * Implements FR-P06: "The Patient shall be able to update their patient
 * summary, including demographic and contact information."
 *
 * Both endpoints act on the *currently authenticated* patient only — there
 * is no {id} route param. A patient can only ever read/edit their own
 * record. Combined with RoleMiddleware('patient') on the route group,
 * this means a Doctor or Facility Admin token can never reach this
 * controller at all, and a Patient token can never reach another
 * patient's row by guessing an ID.
 *
 * Fields intentionally left out of the editable set:
 *   - nupi   : the National Unique Patient Identifier is an immutable
 *              system-assigned ID, not something the patient edits.
 *   - email  : changing the login identity belongs to a dedicated
 *              account-security flow (out of scope for FR-P06).
 *   - password: same reasoning — not "demographic/contact information".
 */
class PatientController extends Controller
{
    /**
     * GET /api/patient/profile
     * Returns the authenticated patient's combined user + patient record.
     */
    public function profile(Request $request)
    {
        $user = $request->user();
        $patient = $user->patient;

        if (!$patient) {
            return response()->json([
                'error' => 'No patient record found for this account.',
            ], 404);
        }

        return response()->json($this->formatProfile($user, $patient));
    }

    /**
     * PUT /api/patient/profile
     * Updates demographic and contact information (FR-P06).
     * Partial updates are allowed — only send the fields you want to change.
     */
    public function updateProfile(Request $request)
    {
        $user = $request->user();
        $patient = $user->patient;

        if (!$patient) {
            return response()->json([
                'error' => 'No patient record found for this account.',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name'                 => 'sometimes|string|max:255',
            'dob'                  => 'sometimes|date|before:today',
            'phone'                => 'sometimes|string|max:20',
            'next_of_kin_name'     => 'nullable|string|max:255',
            'next_of_kin_phone'    => 'nullable|string|max:20',
            'data_sharing_consent' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $changedFields = array_keys($request->only([
            'name', 'dob', 'phone', 'next_of_kin_name',
            'next_of_kin_phone', 'data_sharing_consent',
        ]));

        DB::transaction(function () use ($request, $user, $patient) {
            if ($request->has('name')) {
                $user->update(['name' => $request->input('name')]);
            }

            $patient->update($request->only([
                'dob', 'phone', 'next_of_kin_name',
                'next_of_kin_phone', 'data_sharing_consent',
            ]));
        });

        // Kept consistent with the LOGIN/LOGOUT/REGISTER audit trail already
        // established in AuthController — strictly NFR-07 only mandates this
        // for health records/consent decisions, but logging profile changes
        // costs nothing and keeps the audit story coherent end-to-end.
        AuditLog::record(
            $user->id,
            'UPDATE_PROFILE',
            'success',
            'Patient',
            $patient->id,
            ['fields' => $changedFields]
        );

        return response()->json([
            'message' => 'Profile updated successfully',
            'patient' => $this->formatProfile($user->fresh(), $patient->fresh()),
        ]);
    }

    private function formatProfile($user, $patient): array
    {
        return [
            'id'                   => $user->id,
            'name'                 => $user->name,
            'email'                => $user->email,
            'nupi'                 => $patient->nupi,
            'dob'                  => optional($patient->dob)->format('Y-m-d'),
            'phone'                => $patient->phone,
            'next_of_kin_name'     => $patient->next_of_kin_name,
            'next_of_kin_phone'    => $patient->next_of_kin_phone,
            'data_sharing_consent' => $patient->data_sharing_consent,
        ];
    }
}
