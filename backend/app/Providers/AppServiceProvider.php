<?php

namespace App\Providers;

use App\Domain\Compliance\Events\ComplianceDocumentApproved;
use App\Domain\Compliance\Listeners\SyncEntityExpiryDate;
use App\Domain\Contract\Models\Contract;
use App\Domain\Contract\Policies\ContractPolicy;
use App\Domain\Fuel\Models\FuelStock;
use App\Domain\Fuel\Models\FuelTransaction;
use App\Domain\Fuel\Policies\FuelPolicy;
use App\Domain\Garage\Models\MaintenanceRecord;
use App\Domain\Garage\Policies\MaintenanceRecordPolicy;
use App\Domain\Notification\Models\Notification;
use App\Domain\Notification\Models\NotificationPreference;
use App\Domain\Notification\Policies\NotificationPolicy;
use App\Domain\Notification\Policies\NotificationPreferencePolicy;
use App\Domain\Reporting\Policies\ReportPolicy;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::define('viewApiDocs', function ($user) {
            return $user->hasRole('system_administrator');
        });

        Gate::policy(FuelStock::class, FuelPolicy::class);
        Gate::policy(FuelTransaction::class, FuelPolicy::class);
        Gate::policy(MaintenanceRecord::class, MaintenanceRecordPolicy::class);
        Gate::policy(Contract::class, ContractPolicy::class);
        Gate::policy(Notification::class, NotificationPolicy::class);
        Gate::policy(NotificationPreference::class, NotificationPreferencePolicy::class);

        Gate::define('reports.view', [ReportPolicy::class, 'view']);
        Gate::define('reports.generate', [ReportPolicy::class, 'generate']);

        Event::listen(
            ComplianceDocumentApproved::class,
            SyncEntityExpiryDate::class,
        );
    }
}
