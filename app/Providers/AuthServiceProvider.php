<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\Branch;
use App\Models\Customer;
use App\Models\Item;
use App\Models\Purchase;
use App\Models\Transfer;
use App\Models\User;
use App\Enums\UserRole;
use App\Policies\BranchPolicy;
use App\Policies\CustomerPolicy;
use App\Policies\ItemPolicy;
use App\Policies\PurchasePolicy;
use App\Policies\TransferPolicy;
use App\Policies\UserPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Item::class => ItemPolicy::class,
        Branch::class => BranchPolicy::class,
        Customer::class => CustomerPolicy::class,
        Purchase::class => PurchasePolicy::class,
        Transfer::class => TransferPolicy::class,
        User::class => UserPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        // Register policies
        $this->registerPolicies();

        // Implicitly grant SuperAdmin and GeneralManager roles all permissions
        Gate::before(function (User $user, string $ability) {
            return $user->isSuperAdmin() || $user->isGeneralManager() ? true : null;
        });
    }
}