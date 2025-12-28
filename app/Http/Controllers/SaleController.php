<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use Illuminate\Http\Request;

class SaleController extends Controller
{
    /**
     * Display the print view for a sale invoice.
     */
    public function print(Sale $sale)
    {
        // Load necessary relationships
        $sale->load(['items.item', 'customer', 'warehouse', 'user', 'bankAccount']);
        
        return view('sales-print', compact('sale'));
    }
}