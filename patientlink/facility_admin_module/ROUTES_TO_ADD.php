<?php
// Add this to routes/api.php
// Also add this import at the top:
// use App\Http\Controllers\Api\FacilityAdminController;

// Replace the empty facility-admin route group with this:
Route::middleware(['auth:api', 'role:facility_admin'])->prefix('facility-admin')->group(function () {
    Route::get('/staff',                          [FacilityAdminController::class, 'listStaff']);
    Route::post('/staff',                         [FacilityAdminController::class, 'createStaff']);
    Route::put('/staff/{userId}/deactivate',      [FacilityAdminController::class, 'deactivateStaff']);
    Route::put('/staff/{userId}/activate',        [FacilityAdminController::class, 'activateStaff']);
});
