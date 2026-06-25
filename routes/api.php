<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
// Future controllers (built in later sprints, per roadmap):
// use App\Http\Controllers\Api\PatientController;
// use App\Http\Controllers\Api\ConsentController;
// use App\Http\Controllers\Api\HealthRecordController;
// use App\Http\Controllers\Api\FacilityController;
// use App\Http\Controllers\Api\AuditLogController;
// use App\Http\Controllers\Api\AnalyticsController;

/*
|--------------------------------------------------------------------------
| PatientLink API Routes
|--------------------------------------------------------------------------
|
| All routes are prefixed with /api (Laravel default for routes/api.php).
| JWT auth is provided by tymon/jwt-auth via the 'api' guard.
| RBAC is enforced per-role via the `role` middleware (RoleMiddleware).
|
*/

// ── Public routes ──────────────────────────────────────────────
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// ── Authenticated routes (any role) ────────────────────────────
Route::middleware('auth:api')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/refresh', [AuthController::class, 'refresh']);
});

// ── Doctor-only routes (FR-D01 to FR-D04) ──────────────────────
Route::middleware(['auth:api', 'role:doctor'])->prefix('doctor')->group(function () {
    // Route::get('/patients/{nupi}', [PatientController::class, 'searchByNupi']);       // FR-D02
    // Route::post('/consent-requests', [ConsentController::class, 'requestAccess']);    // FR-D03
    // Route::get('/patients/{nupi}/summary', [HealthRecordController::class, 'summary']); // FR-D04
});

// ── Patient-only routes (FR-P01 to FR-P06) ─────────────────────
Route::middleware(['auth:api', 'role:patient'])->prefix('patient')->group(function () {
    // Route::get('/consent-requests', [ConsentController::class, 'myRequests']);        // FR-P02
    // Route::post('/consent-requests/{id}/approve', [ConsentController::class, 'approve']); // FR-P03
    // Route::post('/consent-requests/{id}/reject', [ConsentController::class, 'reject']);   // FR-P04
    // Route::get('/consent-history', [ConsentController::class, 'history']);            // FR-P05
    // Route::put('/profile', [PatientController::class, 'updateProfile']);              // FR-P06
});

// ── Facility Admin routes (FR-FA01 to FR-FA04) ─────────────────
Route::middleware(['auth:api', 'role:facility_admin'])->prefix('facility-admin')->group(function () {
    // Route::post('/facilities', [FacilityController::class, 'register']);              // FR-FA02
    // Route::get('/staff', [FacilityController::class, 'listStaff']);                   // FR-FA03
    // Route::post('/staff/{id}/deactivate', [FacilityController::class, 'deactivate']); // FR-FA03
    // Route::put('/staff/{id}/role', [FacilityController::class, 'assignRole']);        // FR-FA04
});

// ── MOH Admin routes (FR-M01 to FR-M04) ────────────────────────
Route::middleware(['auth:api', 'role:moh_admin'])->prefix('moh-admin')->group(function () {
    // Route::get('/audit-logs', [AuditLogController::class, 'index']);                  // FR-M02
    // Route::get('/analytics', [AnalyticsController::class, 'dashboard']);              // FR-M03
    // Route::get('/reports', [AnalyticsController::class, 'generateReport']);           // FR-M04
});

// ── Routes shared across multiple roles (example) ──────────────
Route::middleware(['auth:api', 'role:facility_admin,moh_admin'])->group(function () {
    // Route::get('/facilities', [FacilityController::class, 'index']);
});
