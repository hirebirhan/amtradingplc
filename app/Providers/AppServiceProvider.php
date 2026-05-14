<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Relations\Relation;
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
    public function register(): void
    {
        $this->app->singleton(
            \Illuminate\Contracts\Debug\ExceptionHandler::class,
            \App\Exceptions\Handler::class
        );

        $this->app->bind(StatsServiceInterface::class, StatsService::class);
        $this->app->bind(ActivityServiceInterface::class, ActivityService::class);
        $this->app->bind(InventoryServiceInterface::class, InventoryService::class);
        $this->app->bind(ChartDataServiceInterface::class, ChartDataService::class);
    }

    public function boot(): void
    {
        Relation::morphMap([
            'purchase' => \App\Models\Purchase::class,
            'sale'     => \App\Models\Sale::class,
            'transfer' => \App\Models\Transfer::class,
        ]);
    }
}
