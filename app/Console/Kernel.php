<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected $commands = [
        Commands\ClearItemData::class,
        Commands\TestPurchaseScenarios::class,
        Commands\CleanupExpiredReservations::class,
        Commands\GenerateStockReport::class,
    ];

    protected function schedule(Schedule $schedule): void
    {
        // Clean up expired stock reservations every hour
        $schedule->command('stock:cleanup-reservations')
                 ->hourly()
                 ->withoutOverlapping()
                 ->runInBackground();
    }

    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}