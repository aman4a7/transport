<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trip_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trip_id')->constrained()->cascadeOnDelete();
            $table->foreignId('passenger_id')->constrained()->restrictOnDelete();
            $table->string('status', 20)->default('confirmed')->comment('confirmed, cancelled, boarded, no_show');
            $table->timestamps();

            $table->unique(['trip_id', 'passenger_id']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trip_assignments');
    }
};
