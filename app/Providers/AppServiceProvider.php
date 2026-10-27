<?php

namespace App\Providers;

use App\Models\AccountTreatmentRequest;
use App\Models\BankLodgement;
use App\Models\CashierShift;
use App\Models\CollectionBatch;
use App\Models\CollectionDiscrepancy;
use App\Models\Payment;
use App\Models\ReconciliationRun;
use App\Models\ReportExport;
use App\Models\User;
use App\Policies\AccountTreatmentRequestPolicy;
use App\Policies\BankLodgementPolicy;
use App\Policies\CashierShiftPolicy;
use App\Policies\CollectionBatchPolicy;
use App\Policies\CollectionDiscrepancyPolicy;
use App\Policies\PaymentPolicy;
use App\Policies\ReconciliationRunPolicy;
use App\Policies\ReportExportPolicy;
use App\Support\PermissionRegistry;
use App\Support\RoleRegistry;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Gate::before(function (User $user, string $ability): ?bool {
            if (
                $user->hasRole(RoleRegistry::ADMINISTRATOR)
                && in_array($ability, PermissionRegistry::all(), true)
            ) {
                return true;
            }

            return null;
        });

        Gate::policy(Payment::class, PaymentPolicy::class);
        Gate::policy(CollectionBatch::class, CollectionBatchPolicy::class);
        Gate::policy(BankLodgement::class, BankLodgementPolicy::class);
        Gate::policy(AccountTreatmentRequest::class, AccountTreatmentRequestPolicy::class);
        Gate::policy(CollectionDiscrepancy::class, CollectionDiscrepancyPolicy::class);
        Gate::policy(CashierShift::class, CashierShiftPolicy::class);
        Gate::policy(ReconciliationRun::class, ReconciliationRunPolicy::class);
        Gate::policy(ReportExport::class, ReportExportPolicy::class);

        Paginator::useTailwind();

        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }
    }
}
