<?php

// Test script to sync sale status with credit payments
require_once 'vendor/autoload.php';

use App\Models\Sale;
use App\Models\Credit;

// Find the sale with 85,550 amount that shows as "Partially Paid"
$sale = Sale::where('total_amount', 85550)->first();

if (!$sale) {
    echo "Sale not found\n";
    exit;
}

echo "Sale ID: {$sale->id}\n";
echo "Sale Status: {$sale->payment_status}\n";
echo "Sale Total: {$sale->total_amount}\n";
echo "Sale Paid: {$sale->paid_amount}\n";
echo "Sale Due: {$sale->due_amount}\n\n";

// Find associated credit
$credit = Credit::where('reference_type', 'sale')
    ->where('reference_id', $sale->id)
    ->first();

if (!$credit) {
    echo "No credit found for this sale\n";
    exit;
}

echo "Credit ID: {$credit->id}\n";
echo "Credit Amount: {$credit->amount}\n";
echo "Credit Paid: {$credit->paid_amount}\n";
echo "Credit Balance: {$credit->balance}\n";
echo "Credit Status: {$credit->status}\n\n";

// Manually sync the sale status based on credit
if ($credit->balance <= 0) {
    $sale->payment_status = 'paid';
    $sale->paid_amount = $credit->paid_amount;
    $sale->due_amount = 0;
} elseif ($credit->paid_amount > 0) {
    $sale->payment_status = 'partial';
    $sale->paid_amount = $credit->paid_amount;
    $sale->due_amount = $credit->balance;
} else {
    $sale->payment_status = 'due';
    $sale->paid_amount = 0;
    $sale->due_amount = $credit->amount;
}

$sale->save();

echo "Updated Sale Status: {$sale->payment_status}\n";
echo "Updated Sale Paid: {$sale->paid_amount}\n";
echo "Updated Sale Due: {$sale->due_amount}\n";