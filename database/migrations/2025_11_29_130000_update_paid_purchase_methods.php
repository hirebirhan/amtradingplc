<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\Purchase;

return new class extends Migration
{
    public function up(): void
    {
        // Update existing paid purchases from full_credit to cash
        Purchase::where('payment_method', 'full_credit')
            ->where('payment_status', 'paid')
            ->update(['payment_method' => 'cash']);
    }

    public function down(): void
    {
        // Revert paid purchases back to full_credit if needed
        Purchase::where('payment_method', 'cash')
            ->where('payment_status', 'paid')
            ->whereHas('credits', function($query) {
                $query->where('credit_type', 'payable')
                      ->where('status', 'paid');
            })
            ->update(['payment_method' => 'full_credit']);
    }
};