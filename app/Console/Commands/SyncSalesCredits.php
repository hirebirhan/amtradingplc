<?php

namespace App\Console\Commands;

use App\Models\Sale;
use Illuminate\Console\Command;

class SyncSalesCredits extends Command
{
    protected $signature = 'sales:sync-credits';
    protected $description = 'Create missing credit records for sales with outstanding balances';

    public function handle()
    {
        $this->info('Syncing sales credits...');
        
        $salesWithoutCredits = Sale::whereIn('payment_status', ['due', 'partial'])
            ->whereDoesntHave('credit')
            ->where('due_amount', '>', 0)
            ->get();
            
        $count = 0;
        foreach ($salesWithoutCredits as $sale) {
            try {
                $sale->createCreditRecord();
                $count++;
                $this->line("Created credit for sale: {$sale->reference_no}");
            } catch (\Exception $e) {
                $this->error("Failed to create credit for sale {$sale->reference_no}: {$e->getMessage()}");
            }
        }
        
        $this->info("Successfully created {$count} credit records.");
        return 0;
    }
}