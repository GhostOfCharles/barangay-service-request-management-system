<?php

namespace App\Providers;

use App\Models\RequestDocument;
use App\Models\Resident;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Pagination\Paginator;
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
        Paginator::useBootstrapFive();

        // Administrator only: user accounts, service types/fees/checklists, and Mark Completed.
        Gate::define('administer', fn (User $user): bool => $user->isAdministrator());

        // Uploaded IDs and documents: Staff and the Administrator, or the resident they belong to.
        Gate::define('view-resident-files', fn (User $user, Resident $resident): bool => $user->isPersonnel()
            || ($user->isResident() && $resident->user_id === $user->id));

        Gate::define('view-request-document', fn (User $user, RequestDocument $document): bool => $user->isPersonnel()
            || ($user->isResident() && $document->serviceRequest->resident->user_id === $user->id));

        // A resident sees only his or her own tickets; personnel see all.
        Gate::define('view-ticket', fn (User $user, Ticket $ticket): bool => $user->isPersonnel()
            || ($user->isResident() && $ticket->resident->user_id === $user->id));
    }
}
