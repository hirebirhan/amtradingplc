<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Observers\AuditObserver;

// Import all models that need audit tracking
use App\Models\Item;
use App\Models\Category;
use App\Models\Sale;
use App\Models\Purchase;
use App\Models\Transfer;
use App\Models\Credit;
use App\Models\Customer;
use App\Models\Supplier;
use App\Models\Stock;
use App\Models\Branch;
use App\Models\Warehouse;
use App\Models\User;

class AuditServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Models that should have audit tracking
        $auditableModels = [
            Item::class,
            Category::class,
            Sale::class,
            Purchase::class,
            Transfer::class,
            Credit::class,
            Customer::class,
            Supplier::class,
            Stock::class,
            Branch::class,
            Warehouse::class,
            User::class,
        ];

        // Register the audit observer for each model
        foreach ($auditableModels as $model) {
            $model::observe(AuditObserver::class);
        }
    }
}
