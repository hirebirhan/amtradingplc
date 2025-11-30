<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\{Sale, Credit};

return new class extends Migration
{
    public function up()
    {
        // Skip if tables don't exist yet
        if (!\Schema::hasTable('sales') || !\Schema::hasTable('credits')) {
            return;
        }
        
        // Create missing credits using Eloquent
        Sale::whereIn('payment_status', ['due', 'partial'])
            ->where('due_amount', '>', 0)
            ->whereDoesntHave('credit')
            ->each(fn($sale) => $sale->createCreditRecord());

        // Sync existing credits using Eloquent
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
    }

    public function down() {}
};