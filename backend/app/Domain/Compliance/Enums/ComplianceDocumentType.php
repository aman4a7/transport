<?php

namespace App\Domain\Compliance\Enums;

enum ComplianceDocumentType: string
{
    case VehicleRegistration = 'vehicle_registration';
    case Insurance = 'insurance';
    case DriverLicense = 'driver_license';
    case ContractDocument = 'contract_document';
    case Other = 'other';
}
