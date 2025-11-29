<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Fix existing Full Credit purchases that have incorrect payment_status
        DB::table('purchases')
            ->whereIn('payment_method', ['credit_full', 'full_credit'])
            ->where('paid_amount', 0)
            ->where('due_amount', '>', 0)
            ->update(['payment_status' => 'due']);
            
        // Also fix any Credit with Advance that should be partial
        DB::table('purchases')
            ->where('payment_method', 'credit_advance')
            ->where('paid_amount', '>', 0)
            ->where('due_amount', '>', 0)
            ->update(['payment_status' => 'partial']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Optionally revert changes if needed
        // This is generally not recommended for data fixes
    }
};