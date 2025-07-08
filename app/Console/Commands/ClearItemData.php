<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ClearItemData extends Command
{
    protected $signature = 'data:clear-items';
    protected $description = 'Clear all items and price history data';

    public function handle()
    {
        if (!$this->confirm('Are you sure you want to clear all items and price history data? This action cannot be undone.')) {
            $this->info('Operation cancelled.');
            return;
        }

        try {
            // Disable foreign key checks
            DB::statement('SET FOREIGN_KEY_CHECKS=0');

            // Clear the tables if they exist
            if (Schema::hasTable('items')) {
                DB::table('items')->truncate();
                $this->info('Cleared items table.');
            } else {
                $this->warn('Items table does not exist.');
            }

            if (Schema::hasTable('price_histories')) {
                DB::table('price_histories')->truncate();
                $this->info('Cleared price_histories table.');
            } else {
                $this->warn('Price histories table does not exist.');
            }

            // Re-enable foreign key checks
            DB::statement('SET FOREIGN_KEY_CHECKS=1');

            $this->info('Operation completed successfully.');
        } catch (\Exception $e) {
            $this->error('An error occurred while clearing the data: ' . $e->getMessage());
        }
    }
}