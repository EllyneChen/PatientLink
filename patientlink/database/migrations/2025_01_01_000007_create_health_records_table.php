<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * health_records table — encrypted patient health records,
     * scoped to the facility that created them.
     */
    public function up(): void
    {
        Schema::create('health_records', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('patient_nupi');
            $table->uuid('facility_id');
            $table->json('summary'); // structured clinical data
            $table->boolean('encrypted')->default(true);
            $table->timestamps();

            $table->foreign('patient_nupi')->references('nupi')->on('patients');
            $table->foreign('facility_id')->references('id')->on('facilities');

            $table->index('patient_nupi');
            $table->index('facility_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('health_records');
    }
};
