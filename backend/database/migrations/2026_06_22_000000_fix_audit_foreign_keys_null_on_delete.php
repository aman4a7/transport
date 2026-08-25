<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Fix drivers.created_by, drivers.updated_by
        $this->dropForeignIfExists('drivers', 'drivers_created_by_foreign');
        $this->dropForeignIfExists('drivers', 'drivers_updated_by_foreign');
        Schema::table('drivers', function ($table): void {
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();
        });

        // Fix owners.created_by, owners.updated_by
        $this->dropForeignIfExists('owners', 'owners_created_by_foreign');
        $this->dropForeignIfExists('owners', 'owners_updated_by_foreign');
        Schema::table('owners', function ($table): void {
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();
        });

        // Fix vehicles.created_by, vehicles.updated_by
        $this->dropForeignIfExists('vehicles', 'vehicles_created_by_foreign');
        $this->dropForeignIfExists('vehicles', 'vehicles_updated_by_foreign');
        Schema::table('vehicles', function ($table): void {
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();
        });

        // Fix passengers.created_by, passengers.updated_by
        $this->dropForeignIfExists('passengers', 'passengers_created_by_foreign');
        $this->dropForeignIfExists('passengers', 'passengers_updated_by_foreign');
        Schema::table('passengers', function ($table): void {
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();
        });

        // Fix routes.created_by, routes.updated_by
        $this->dropForeignIfExists('routes', 'routes_created_by_foreign');
        $this->dropForeignIfExists('routes', 'routes_updated_by_foreign');
        Schema::table('routes', function ($table): void {
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();
        });

        // Fix trips.created_by, trips.updated_by
        $this->dropForeignIfExists('trips', 'trips_created_by_foreign');
        $this->dropForeignIfExists('trips', 'trips_updated_by_foreign');
        Schema::table('trips', function ($table): void {
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();
        });

        // Fix fuel_stocks.created_by, fuel_stocks.updated_by
        $this->dropForeignIfExists('fuel_stocks', 'fuel_stocks_created_by_foreign');
        $this->dropForeignIfExists('fuel_stocks', 'fuel_stocks_updated_by_foreign');
        Schema::table('fuel_stocks', function ($table): void {
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();
        });

        // Fix compliance_documents.reviewed_by
        $this->dropForeignIfExists('compliance_documents', 'compliance_documents_reviewed_by_foreign');
        Schema::table('compliance_documents', function ($table): void {
            $table->foreign('reviewed_by')->references('id')->on('users')->nullOnDelete();
        });

        // Fix fuel_transactions.issued_by
        $this->dropForeignIfExists('fuel_transactions', 'fuel_transactions_issued_by_foreign');
        Schema::table('fuel_transactions', function ($table): void {
            $table->foreign('issued_by')->references('id')->on('users')->nullOnDelete();
        });

        // Fix contracts.owner_id (H2: cascadeOnDelete -> restrictOnDelete)
        $this->dropForeignIfExists('contracts', 'contracts_owner_id_foreign');
        Schema::table('contracts', function ($table): void {
            $table->foreign('owner_id')->references('id')->on('owners')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        $tables = [
            'drivers' => ['created_by', 'updated_by'],
            'owners' => ['created_by', 'updated_by'],
            'vehicles' => ['created_by', 'updated_by'],
            'passengers' => ['created_by', 'updated_by'],
            'routes' => ['created_by', 'updated_by'],
            'trips' => ['created_by', 'updated_by'],
            'fuel_stocks' => ['created_by', 'updated_by'],
            'compliance_documents' => ['reviewed_by'],
            'fuel_transactions' => ['issued_by'],
        ];

        foreach ($tables as $table => $columns) {
            foreach ($columns as $column) {
                $this->dropForeignIfExists($table, "{$table}_{$column}_foreign");
            }
            Schema::table($table, function ($table) use ($columns): void {
                foreach ($columns as $column) {
                    $table->foreign($column)->references('id')->on('users');
                }
            });
        }

        // Restore contracts.owner_id cascadeOnDelete
        $this->dropForeignIfExists('contracts', 'contracts_owner_id_foreign');
        Schema::table('contracts', function ($table): void {
            $table->foreign('owner_id')->references('id')->on('owners')->cascadeOnDelete();
        });
    }

    private function dropForeignIfExists(string $table, string $constraintName): void
    {
        Schema::table($table, function ($table) use ($constraintName): void {
            $table->dropForeign($constraintName);
        });
    }
};
