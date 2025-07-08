<?php

namespace App\Providers;

use App\Utilities\ConnectionChecker;
use Illuminate\Support\ServiceProvider;

class ConnectionServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(ConnectionChecker::class, function ($app) {
            return new ConnectionChecker();
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
} 