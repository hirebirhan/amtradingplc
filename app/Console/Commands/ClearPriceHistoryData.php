<?php

namespace App\Console\Commands;

use App\Models\PriceHistory;
use Illuminate\Console\Command;

class ClearPriceHistoryData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'price-history:clear';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remove all price history data from the database';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting price history data cleanup...');

        if ($this->confirm('Are you sure you want to remove all price history data? This action cannot be undone.', true)) {
            $count = PriceHistory::count();

            if ($count === 0) {
                $this->info('No price history records found. Nothing to delete.');
                return 0;
            }

            // Truncate the price_histories table
            PriceHistory::truncate();

            $this->info("Successfully deleted {$count} price history records.");
            $this->info('All price history data has been removed!');

            return 0;
        }

        $this->info('Operation cancelled.');
        return 1;
    }
}
