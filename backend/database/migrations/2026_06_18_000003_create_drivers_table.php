<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('drivers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('license_number', 50)->unique();
            $table->string('license_category', 30)->comment('light, medium, heavy, trailer');
            $table->date('license_expiry');
            $table->string('status', 30)->default('active')->comment('active, suspended, expired, inactive');
            $table->date('medical_expiry')->nullable();
            $table->foreignId('assigned_vehicle_id')->nullable()->constrained('vehicles')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
            $table->index('license_expiry');
            $table->index('medical_expiry');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('drivers');
    }
};
