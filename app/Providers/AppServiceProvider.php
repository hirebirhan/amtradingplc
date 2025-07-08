<?php

namespace App\Providers;

use App\Livewire\Items\ItemForm;
use App\View\Components\AppLayout;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Relations\Relation;
use Livewire\Livewire;
use App\Services\Dashboard\Contracts\{
    StatsServiceInterface,
    ActivityServiceInterface,
    InventoryServiceInterface,
    ChartDataServiceInterface
};
use App\Services\Dashboard\{
    StatsService,
    ActivityService,
    InventoryService,
    ChartDataService
};

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register our custom exception handler
        $this->app->singleton(
            \Illuminate\Contracts\Debug\ExceptionHandler::class,
            \App\Exceptions\Handler::class
        );

        // Dashboard Services
        $this->app->bind(StatsServiceInterface::class, StatsService::class);
        $this->app->bind(ActivityServiceInterface::class, ActivityService::class);
        $this->app->bind(InventoryServiceInterface::class, InventoryService::class);
        $this->app->bind(ChartDataServiceInterface::class, ChartDataService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Define morphMap for polymorphic relationships
        Relation::morphMap([
            'purchase' => \App\Models\Purchase::class,
            'sale' => \App\Models\Sale::class,
            'transfer' => \App\Models\Transfer::class,
        ]);

        // Use Bootstrap for Laravel's default pagination
        Paginator::useBootstrap();
        
        // Override the pagination views to remove duplicate pagination information
        // This will keep the navigation buttons but remove the top text about showing items
        Paginator::defaultView('pagination.custom');
        Paginator::defaultSimpleView('pagination.simple-custom');

        // For Livewire pagination, use Bootstrap theme for consistency
        config(['livewire.pagination_theme' => 'bootstrap']);

        // Register Livewire components with aliases
        Livewire::component('items.item-form', ItemForm::class);
        Livewire::component('employees.index', \App\Livewire\Employees\Index::class);
        Livewire::component('employees.create', \App\Livewire\Employees\Create::class);
        Livewire::component('employees.show', \App\Livewire\Employees\Show::class);
        Livewire::component('employees.edit', \App\Livewire\Employees\Edit::class);

        // Register Blade components
        Blade::component('app-layout', AppLayout::class);

        // Register Livewire components
        Livewire::component('purchases.create', \App\Livewire\Purchases\Create::class);
        Livewire::component('items.create', \App\Livewire\Items\Create::class);
    }
}
