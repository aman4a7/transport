<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * EXAMPLE: Vehicles table migration using the database-schema skill templates.
 *
 * Demonstrates:
 * - Standard columns (id, timestamps, softDeletes)
 * - Category column for defence_plated / contracted_private
 * - Status column with string type (not database ENUM)
 * - Foreign key with nullable + nullOnDelete for optional owner
 * - Audit columns (created_by, updated_by)
 * - Composite unique constraint (plate_number + category)
 * - Indexes on filtered columns
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicles', function (Blueprint $table) {
            // Primary key
            $table->id();

            // --- Foreign Keys ---
            $table->foreignId('owner_id')
                ->nullable()
                ->constrained('vehicle_owners')
                ->nullOnDelete()
                ->comment('Owner for contracted_private vehicles; null for defence_plated');

            // --- Core Fields ---
            $table->string('plate_number', 20);
            $table->string('make', 100);
            $table->string('model', 100);
            $table->unsignedSmallInteger('year');
            $table->string('color', 30)->nullable();
            $table->string('vin', 17)->nullable()->comment('Vehicle Identification Number');
            $table->unsignedInteger('seating_capacity')->nullable();
            $table->string('fuel_type', 20)->default('diesel')
                ->comment('diesel, petrol, electric, hybrid');

            // --- Category & Status ---
            $table->string('category', 30)
                ->comment('defence_plated or contracted_private');
            $table->string('status', 30)->default('active')
                ->comment('active, in_maintenance, suspended, decommissioned');
            $table->timestamp('status_changed_at')->nullable();

            // --- Compliance ---
            $table->date('registration_expiry')->nullable();
            $table->date('insurance_expiry')->nullable();
            $table->date('inspection_expiry')->nullable();

            // --- Audit Columns ---
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');

            // --- Standard Timestamps ---
            $table->timestamps();
            $table->softDeletes();

            // --- Indexes ---
            $table->index('category');
            $table->index('status');
            $table->index(['category', 'status']);
            $table->index('owner_id'); // Already indexed by constrained(), but explicit for clarity

            // --- Unique Constraints ---
            $table->unique(['plate_number', 'category']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
};
