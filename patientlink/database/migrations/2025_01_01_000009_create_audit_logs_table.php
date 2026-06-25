<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * audit_logs table — immutable append-only log of all
     * significant system events (NFR-07 Auditability).
     * outcome: success | failure | denied
     */
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('actor_id');
            $table->string('action');
            $table->enum('outcome', ['success', 'failure', 'denied']);
            $table->string('entity_type')->nullable();
            $table->uuid('entity_id')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('timestamp')->useCurrent();

            $table->foreign('actor_id')->references('id')->on('users');

            $table->index('actor_id');
            $table->index('timestamp');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
