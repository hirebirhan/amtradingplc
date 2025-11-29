<?php

require_once __DIR__ . '/vendor/autoload.php';

use Illuminate\Support\Facades\DB;

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

try {
    DB::transaction(function () {
        echo "Updating purchase statuses based on business logic...\n";
        
        // Get current date for comparison
        $today = now();
        $oneWeekAgo = $today->copy()->subWeek();
        
        // Recent purchases (within 1 week) with unpaid status - set as confirmed
        $recentConfirmed = DB::table('purchases')
            ->where('status', null)
            ->where('created_at', '>=', $oneWeekAgo)
            ->where('payment_status', '!=', 'paid')
            ->update(['status' => 'confirmed']);
        echo "Updated {$recentConfirmed} recent purchases to 'confirmed' status\n";
        
        // Recent paid purchases - set as received
        $recentReceived = DB::table('purchases')
            ->where('status', null)
            ->where('created_at', '>=', $oneWeekAgo)
            ->where('payment_status', 'paid')
            ->update(['status' => 'received']);
        echo "Updated {$recentReceived} recent paid purchases to 'received' status\n";
        
        // Older purchases with credits - definitely received
        $oldWithCredits = DB::table('purchases')
            ->where('status', null)
            ->where('created_at', '<', $oneWeekAgo)
            ->whereExists(function ($query) {
                $query->select(DB::raw(1))
                      ->from('credits')
                      ->whereColumn('credits.reference_id', 'purchases.id')
                      ->where('credits.reference_type', 'purchase');
            })
            ->update(['status' => 'received']);
        echo "Updated {$oldWithCredits} old purchases with credits to 'received' status\n";
        
        // All other old purchases - assume received
        $remainingOld = DB::table('purchases')
            ->where('status', null)
            ->where('created_at', '<', $oneWeekAgo)
            ->update(['status' => 'received']);
        echo "Updated {$remainingOld} remaining old purchases to 'received' status\n";
        
        echo "Purchase status update completed successfully!\n";
    });
} catch (Exception $e) {
    echo "Error updating purchase statuses: " . $e->getMessage() . "\n";
}