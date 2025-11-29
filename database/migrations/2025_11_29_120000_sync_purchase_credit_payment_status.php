<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Credit;
use App\Models\Purchase;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Sync purchase payment status with credit status
        $credits = Credit::where('credit_type', 'payable')
            ->where('reference_type', 'purchase')
            ->whereNotNull('reference_id')
            ->with('purchase')
            ->get();

        foreach ($credits as $credit) {
            if ($credit->purchase) {
                $purchase = $credit->purchase;
                
                // Update purchase amounts to match credit
                $purchase->paid_amount = $credit->paid_amount;
                $purchase->due_amount = $credit->balance;
                
                // Update purchase payment status based on credit status
                if ($credit->balance <= 0) {
                    $purchase->payment_status = 'paid';
                } elseif ($credit->paid_amount > 0) {
                    $purchase->payment_status = 'partial';
                } else {
                    $purchase->payment_status = 'due';
                }
                
                $purchase->save();
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No rollback needed - this is a data sync operation
    }
};