<?php
// Add this block to routes/api.php
// Place it AFTER the patient route group

use App\Http\Controllers\Api\DoctorController;

Route::middleware(['auth:api', 'role:doctor'])->prefix('doctor')->group(function () {
    Route::get('/search',           [DoctorController::class, 'searchPatient']);
    Route::post('/consent/request', [DoctorController::class, 'requestConsent']);
    Route::post('/consent/verify',  [DoctorController::class, 'verifyConsent']);
    Route::get('/records/{nupi}',   [DoctorController::class, 'viewRecords']);
});
