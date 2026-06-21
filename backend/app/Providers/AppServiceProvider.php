<?php

namespace App\Providers;

use App\Domain\Compliance\Events\ComplianceDocumentApproved;
use App\Domain\Compliance\Listeners\SyncEntityExpiryDate;
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

        Event::listen(
            ComplianceDocumentApproved::class,
            SyncEntityExpiryDate::class,
        );
    }
}
