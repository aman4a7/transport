<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique()->comment('Human-readable permission name');
            $table->string('slug', 100)->unique()->comment('Machine-readable permission identifier (e.g., vehicles.view)');
            $table->string('group', 50)->comment('Permission group for UI organization (e.g., vehicles, drivers, fuel)');
            $table->text('description')->nullable();
            $table->boolean('is_system')->default(false)->comment('System permissions cannot be deleted');
            $table->timestamps();
            $table->softDeletes();

            $table->index('slug');
            $table->index('group');
            $table->index('is_system');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('permissions');
    }
};
