<?php

namespace App\Domain\Reporting\Enums;

enum ReportType: string
{
    case FleetSummary = 'fleet_summary';
    case FuelConsumption = 'fuel_consumption';
    case TripAnalysis = 'trip_analysis';
    case MaintenanceSummary = 'maintenance_summary';
    case ComplianceStatus = 'compliance_status';
    case ContractPerformance = 'contract_performance';
    case PassengerUtilization = 'passenger_utilization';

    public function label(): string
    {
        return match ($this) {
            self::FleetSummary => 'Fleet Summary',
            self::FuelConsumption => 'Fuel Consumption',
            self::TripAnalysis => 'Trip Analysis',
            self::MaintenanceSummary => 'Maintenance Summary',
            self::ComplianceStatus => 'Compliance Status',
            self::ContractPerformance => 'Contract Performance',
            self::PassengerUtilization => 'Passenger Utilization',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::FleetSummary => 'Overview of all vehicles by category and status',
            self::FuelConsumption => 'Fuel usage trends by vehicle, type, and time period',
            self::TripAnalysis => 'Trip volume, completion rates, and distance statistics',
            self::MaintenanceSummary => 'Maintenance requests, costs, and vehicle downtime',
            self::ComplianceStatus => 'Driver and vehicle compliance document status',
            self::ContractPerformance => 'Contract utilization, costs, and expiry tracking',
            self::PassengerUtilization => 'Passenger trip volumes and route demand analysis',
        };
    }
}
