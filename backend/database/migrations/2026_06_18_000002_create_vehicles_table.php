<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')->nullable()->constrained()->nullOnDelete();
            $table->string('plate_number', 20);
            $table->string('make', 100);
            $table->string('model', 100);
            $table->unsignedSmallInteger('year');
            $table->string('color', 30)->nullable();
            $table->string('vin', 17)->nullable()->comment('Vehicle Identification Number');
            $table->string('engine_number', 50)->nullable();
            $table->unsignedInteger('seating_capacity')->nullable();
            $table->string('fuel_type', 20)->default('diesel')->comment('diesel, petrol, electric, hybrid');
            $table->string('category', 30)->comment('defence_plated or contracted_private');
            $table->string('status', 30)->default('active')->comment('active, in_maintenance, suspended, decommissioned');
            $table->timestamp('status_changed_at')->nullable();
            $table->date('registration_expiry')->nullable();
            $table->date('insurance_expiry')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->timestamps();
            $table->softDeletes();

            $table->index('category');
            $table->index('status');
            $table->index(['category', 'status']);
            $table->unique(['plate_number', 'category']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
};
