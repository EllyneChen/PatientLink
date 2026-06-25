<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\PatientController;
use App\Http\Controllers\Api\DoctorController;
use App\Http\Controllers\Api\FacilityAdminController;
use App\Http\Controllers\Api\MohAdminController;

Route::post("/register", [AuthController::class, "register"]);
Route::post("/login", [AuthController::class, "login"]);

Route::middleware("auth:api")->group(function () {
    Route::get("/me", [AuthController::class, "me"]);
    Route::post("/logout", [AuthController::class, "logout"]);
    Route::post("/refresh", [AuthController::class, "refresh"]);
});

Route::middleware(["auth:api", "role:doctor"])->prefix("doctor")->group(function () {
    Route::get("/search", [DoctorController::class, "searchPatient"]);
    Route::post("/consent/request", [DoctorController::class, "requestConsent"]);
    Route::post("/consent/verify", [DoctorController::class, "verifyConsent"]);
    Route::get("/records/{nupi}", [DoctorController::class, "viewRecords"]);
    Route::post('/records/{nupi}',  [DoctorController::class, 'addRecord']);
});

Route::middleware(["auth:api", "role:patient"])->prefix("patient")->group(function () {
    Route::get("/profile", [PatientController::class, "profile"]);
    Route::put("/profile", [PatientController::class, "updateProfile"]);
});

Route::middleware(["auth:api", "role:facility_admin"])->prefix("facility-admin")->group(function () {
    Route::get("/staff", [FacilityAdminController::class, "listStaff"]);
    Route::post("/staff", [FacilityAdminController::class, "createStaff"]);
    Route::put("/staff/{userId}/deactivate", [FacilityAdminController::class, "deactivateStaff"]);
    Route::put("/staff/{userId}/activate", [FacilityAdminController::class, "activateStaff"]);
    Route::delete("/staff/{staffId}", [FacilityAdminController::class, "deleteStaff"]);
});

Route::middleware(["auth:api", "role:moh_admin"])->prefix("moh-admin")->group(function () {
    Route::get("/analytics", [MohAdminController::class, "analytics"]);
    Route::get("/audit-logs", [MohAdminController::class, "auditLogs"]);
});
