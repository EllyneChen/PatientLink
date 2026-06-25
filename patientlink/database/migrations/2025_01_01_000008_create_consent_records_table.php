<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * consent_records table — tracks the full OTP-based consent lifecycle.
     * status: pending | approved | rejected | expired
     */
    public function up(): void
    {
        Schema::create('consent_records', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('patient_nupi');
            $table->uuid('doctor_id');
            $table->uuid('facility_id');
            $table->string('otp_hash');
            $table->enum('status', ['pending', 'approved', 'rejected', 'expired'])->default('pending');
            $table->timestamp('expires_at');
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->foreign('patient_nupi')->references('nupi')->on('patients');
            $table->foreign('doctor_id')->references('id')->on('doctors');
            $table->foreign('facility_id')->references('id')->on('facilities');

            $table->index('patient_nupi');
            $table->index('doctor_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consent_records');
    }
};
