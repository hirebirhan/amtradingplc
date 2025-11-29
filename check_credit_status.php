<?php

require_once 'vendor/autoload.php';

// Load Laravel application
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Credit;
use App\Models\Purchase;

echo "=== Credit and Purchase Status Analysis ===\n\n";

// Get all payable credits with their purchases
$credits = Credit::where('credit_type', 'payable')
    ->where('reference_type', 'purchase')
    ->whereNotNull('reference_id')
    ->with('purchase')
    ->get();

echo "Found " . $credits->count() . " payable credits linked to purchases\n\n";

foreach ($credits as $credit) {
    echo "Credit ID: {$credit->id}\n";
    echo "Reference: {$credit->reference_no}\n";
    echo "Amount: " . number_format($credit->amount, 2) . " ETB\n";
    echo "Paid: " . number_format($credit->paid_amount, 2) . " ETB\n";
    echo "Balance: " . number_format($credit->balance, 2) . " ETB\n";
    echo "Credit Status: {$credit->status}\n";
    
    if ($credit->purchase) {
        echo "Purchase Status: {$credit->purchase->payment_status}\n";
        echo "Purchase Paid: " . number_format($credit->purchase->paid_amount, 2) . " ETB\n";
        echo "Purchase Due: " . number_format($credit->purchase->due_amount, 2) . " ETB\n";
        
        // Check for inconsistency
        if ($credit->status === 'paid' && $credit->purchase->payment_status !== 'paid') {
            echo "❌ INCONSISTENCY: Credit is paid but purchase is not!\n";
        } elseif ($credit->balance > 0 && $credit->purchase->payment_status === 'paid') {
            echo "❌ INCONSISTENCY: Purchase is paid but credit has balance!\n";
        } else {
            echo "✅ Status is consistent\n";
        }
    } else {
        echo "❌ No purchase found for this credit!\n";
    }
    
    echo "---\n\n";
}

echo "=== Summary ===\n";
echo "Run the migration to fix inconsistencies:\n";
echo "php artisan migrate --path=database/migrations/2025_11_29_120000_sync_purchase_credit_payment_status.php\n";