<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\BankAccount;
use App\Models\Branch;
use App\Models\Credit;
use App\Models\Customer;
use App\Models\Employee;
use App\Models\Expense;
use App\Models\Item;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\Transfer;
use App\Models\User;
use App\Models\Warehouse;
use App\Policies\BankAccountPolicy;
use App\Policies\BranchPolicy;
use App\Policies\CreditPolicy;
use App\Policies\CustomerPolicy;
use App\Policies\EmployeePolicy;
use App\Policies\ExpensePolicy;
use App\Policies\ItemPolicy;
use App\Policies\PurchasePolicy;
use App\Policies\RolePolicy;
use App\Policies\SalePolicy;
use App\Policies\TransferPolicy;
use App\Policies\UserPolicy;
use App\Policies\WarehousePolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;
use Spatie\Permission\Models\Role;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        BankAccount::class => BankAccountPolicy::class,
        Branch::class => BranchPolicy::class,
        Credit::class => CreditPolicy::class,
        Customer::class => CustomerPolicy::class,
        Employee::class => EmployeePolicy::class,
        Expense::class => ExpensePolicy::class,
        Item::class => ItemPolicy::class,
        Purchase::class => PurchasePolicy::class,
        Role::class => RolePolicy::class,
        Sale::class => SalePolicy::class,
        Transfer::class => TransferPolicy::class,
        User::class => UserPolicy::class,
        Warehouse::class => WarehousePolicy::class,
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
