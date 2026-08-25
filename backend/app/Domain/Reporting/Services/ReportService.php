<?php

namespace App\Domain\Reporting\Services;

use App\Domain\Compliance\Models\ComplianceDocument;
use App\Domain\Contract\Models\Contract;
use App\Domain\Fuel\Models\FuelTransaction;
use App\Domain\Garage\Models\MaintenanceRecord;
use App\Domain\Reporting\Enums\ReportType;
use App\Domain\Trip\Models\Trip;
use App\Domain\Trip\Models\TripAssignment;
use App\Domain\Vehicle\Models\Vehicle;
use Illuminate\Support\Collection;

class ReportService
{
    public function listAvailable(): Collection
    {
        return collect(ReportType::cases())->map(fn (ReportType $type): array => [
            'type' => $type->value,
            'label' => $type->label(),
            'description' => $type->description(),
        ]);
    }

    public function generate(ReportType $type, array $filters = []): array
    {
        return match ($type) {
            ReportType::FleetSummary => $this->fleetSummary($filters),
            ReportType::FuelConsumption => $this->fuelConsumption($filters),
            ReportType::TripAnalysis => $this->tripAnalysis($filters),
            ReportType::MaintenanceSummary => $this->maintenanceSummary($filters),
            ReportType::ComplianceStatus => $this->complianceStatus($filters),
            ReportType::ContractPerformance => $this->contractPerformance($filters),
            ReportType::PassengerUtilization => $this->passengerUtilization($filters),
        };
    }

    private function applyDateFilter($query, array $filters, string $column = 'created_at'): void
    {
        if (! empty($filters['date_from'])) {
            $query->whereDate($column, '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->whereDate($column, '<=', $filters['date_to']);
        }
    }

    private function fleetSummary(array $filters): array
    {
        $query = Vehicle::query();
        $this->applyDateFilter($query, $filters, 'created_at');

        $total = (clone $query)->count();
        $byCategory = (clone $query)->selectRaw('category, count(*) as count')
            ->groupBy('category')->pluck('count', 'category');
        $byStatus = (clone $query)->selectRaw('status, count(*) as count')
            ->groupBy('status')->pluck('count', 'status');

        return [
            'type' => 'fleet_summary',
            'label' => 'Fleet Summary',
            'aggregates' => [
                'total_vehicles' => $total,
                'by_category' => $byCategory,
                'by_status' => $byStatus,
            ],
        ];
    }

    private function fuelConsumption(array $filters): array
    {
        $query = FuelTransaction::query()->where('transaction_type', 'issue');
        $this->applyDateFilter($query, $filters);

        $byFuelType = (clone $query)->selectRaw('fuel_type, sum(abs(quantity)) as total_quantity, count(*) as transaction_count')
            ->groupBy('fuel_type')->get();

        $totalQuantity = (clone $query)->sum('quantity');

        return [
            'type' => 'fuel_consumption',
            'label' => 'Fuel Consumption',
            'aggregates' => [
                'total_quantity_litres' => abs((float) $totalQuantity),
                'total_transactions' => (clone $query)->count(),
                'by_fuel_type' => $byFuelType,
            ],
        ];
    }

    private function tripAnalysis(array $filters): array
    {
        $query = Trip::query();
        $this->applyDateFilter($query, $filters);

        $total = (clone $query)->count();
        $byStatus = (clone $query)->selectRaw('status, count(*) as count')
            ->groupBy('status')->pluck('count', 'status');
        $totalAssignments = TripAssignment::whereIn('trip_id', (clone $query)->select('trips.id'))->count();

        return [
            'type' => 'trip_analysis',
            'label' => 'Trip Analysis',
            'aggregates' => [
                'total_trips' => $total,
                'by_status' => $byStatus,
                'total_assignments' => $totalAssignments,
            ],
        ];
    }

    private function maintenanceSummary(array $filters): array
    {
        $query = MaintenanceRecord::query();
        $this->applyDateFilter($query, $filters);

        $total = (clone $query)->count();
        $byStatus = (clone $query)->selectRaw('status, count(*) as count')
            ->groupBy('status')->pluck('count', 'status');
        $totalCost = (clone $query)->sum('cost');

        return [
            'type' => 'maintenance_summary',
            'label' => 'Maintenance Summary',
            'aggregates' => [
                'total_requests' => $total,
                'by_status' => $byStatus,
                'total_cost' => (float) $totalCost,
            ],
        ];
    }

    private function complianceStatus(array $filters): array
    {
        $query = ComplianceDocument::query();
        $this->applyDateFilter($query, $filters);

        $total = (clone $query)->count();
        $byStatus = (clone $query)->selectRaw('status, count(*) as count')
            ->groupBy('status')->pluck('count', 'status');
        $byEntityType = (clone $query)->selectRaw('documentable_type, count(*) as count')
            ->groupBy('documentable_type')->pluck('count', 'documentable_type');
        $expiring = (clone $query)->whereDate('expires_at', '<=', now()->addDays(30))
            ->whereDate('expires_at', '>', now())->count();
        $expired = (clone $query)->whereDate('expires_at', '<', now())->count();

        return [
            'type' => 'compliance_status',
            'label' => 'Compliance Status',
            'aggregates' => [
                'total_documents' => $total,
                'by_status' => $byStatus,
                'by_entity_type' => $byEntityType,
                'expiring_soon' => $expiring,
                'expired' => $expired,
            ],
        ];
    }

    private function contractPerformance(array $filters): array
    {
        $query = Contract::query();
        $this->applyDateFilter($query, $filters);

        $total = (clone $query)->count();
        $byStatus = (clone $query)->selectRaw('status, count(*) as count')
            ->groupBy('status')->pluck('count', 'status');
        $totalValue = (clone $query)->sum('contract_value');

        return [
            'type' => 'contract_performance',
            'label' => 'Contract Performance',
            'aggregates' => [
                'total_contracts' => $total,
                'by_status' => $byStatus,
                'total_value' => (float) $totalValue,
            ],
        ];
    }

    private function passengerUtilization(array $filters): array
    {
        $query = TripAssignment::query();
        $this->applyDateFilter($query, $filters, 'created_at');

        $totalAssignments = (clone $query)->count();
        $passengerCount = (clone $query)->whereHas('trip', fn ($q) => $q->whereNotNull('route_id'))
            ->count();

        return [
            'type' => 'passenger_utilization',
            'label' => 'Passenger Utilization',
            'aggregates' => [
                'total_assignments' => $totalAssignments,
                'passenger_trips' => $passengerCount,
            ],
        ];
    }
}
