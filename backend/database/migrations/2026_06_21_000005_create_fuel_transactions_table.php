<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fuel_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('fuel_type', 20);
            $table->decimal('quantity', 12, 2)->comment('positive for additions, negative for deductions');
            $table->string('transaction_type', 20)->comment('issue, restock, adjustment');
            $table->decimal('unit_cost', 12, 2)->nullable();
            $table->decimal('total_cost', 12, 2)->nullable();
            $table->foreignId('issued_by')->nullable()->constrained('users');
            $table->text('notes')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index('fuel_type');
            $table->index('transaction_type');
            $table->index('vehicle_id');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fuel_transactions');
    }
};
