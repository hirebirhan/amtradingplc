<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\{Sale, Credit};
use Illuminate\Support\Facades\DB;

class SyncSaleCredits extends Command
{
    protected $signature = 'sale:sync-credits';
    protected $description = 'Sync sale-credit relationships';

    public function handle()
    {
        DB::transaction(function () {
            // Create missing credits
            Sale::whereIn('payment_status', ['due', 'partial'])
                ->where('due_amount', '>', 0)
                ->whereDoesntHave('credit')
                ->each(fn($sale) => $sale->createCreditRecord());

            // Sync existing credits
            Credit::where('reference_type', 'sale')
                ->with('sale')
                ->each(function($credit) {
                    if ($credit->sale) {
                        $credit->update([
                            'amount' => $credit->sale->total_amount,
                            'paid_amount' => $credit->sale->paid_amount,
                            'balance' => $credit->sale->due_amount,
                            'status' => $credit->sale->due_amount <= 0 ? 'paid' : 
                                       ($credit->sale->paid_amount > 0 ? 'partial' : 'active')
                        ]);
                    }
                });
        });

        $this->info('Sale-credit sync completed!');
    }
}