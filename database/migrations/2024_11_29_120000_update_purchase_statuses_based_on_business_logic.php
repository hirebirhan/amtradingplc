<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        // Skip if purchases table doesn't exist yet
        if (!Schema::hasTable('purchases')) {
            return;
        }
        
        // Update existing purchases with logical statuses based on business rules
        DB::transaction(function () {
            // Get current date for comparison
            $today = now();
            $oneWeekAgo = $today->copy()->subWeek();
            
            // Recent purchases (within 1 week) - likely still being processed
            DB::table('purchases')
                ->where('status', null)
                ->where('created_at', '>=', $oneWeekAgo)
                ->where('payment_status', '!=', 'paid')
                ->update(['status' => 'confirmed']);
                
            // Recent paid purchases - likely received
            DB::table('purchases')
                ->where('status', null)
                ->where('created_at', '>=', $oneWeekAgo)
                ->where('payment_status', 'paid')
                ->update(['status' => 'received']);
                
            // Older purchases with payments/credits - definitely received
            DB::table('purchases')
                ->where('status', null)
                ->where('created_at', '<', $oneWeekAgo)
                ->whereExists(function ($query) {
                    $query->select(DB::raw(1))
                          ->from('credits')
                          ->whereColumn('credits.reference_id', 'purchases.id')
                          ->where('credits.reference_type', 'purchase');
                })
                ->update(['status' => 'received']);
                
            // All other old purchases - assume received
            DB::table('purchases')
                ->where('status', null)
                ->where('created_at', '<', $oneWeekAgo)
                ->update(['status' => 'received']);
        });
    }

    public function down()
    {
        // Reset all statuses to null
        DB::table('purchases')->update(['status' => null]);
    }
};