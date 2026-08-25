<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trips', function (Blueprint $table) {
            $table->id();
            $table->foreignId('route_id')->constrained()->restrictOnDelete();
            $table->foreignId('vehicle_id')->constrained()->restrictOnDelete();
            $table->foreignId('driver_id')->constrained()->restrictOnDelete();
            $table->date('scheduled_date');
            $table->time('departure_time');
            $table->time('estimated_arrival_time')->nullable();
            $table->timestamp('actual_departure_time')->nullable();
            $table->timestamp('actual_arrival_time')->nullable();
            $table->string('status', 20)->default('scheduled')->comment('scheduled, in_progress, completed, cancelled');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
            $table->index('scheduled_date');
            $table->index(['route_id', 'scheduled_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trips');
    }
};
