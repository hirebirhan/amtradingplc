<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use Illuminate\Http\Request;
use UserHelper;

class SaleController extends Controller
{
    /**
     * Print the specified sale.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function print($id)
    {
        $sale = Sale::with(['customer', 'warehouse', 'user', 'items.item', 'payments'])
            ->findOrFail($id);
            
        // Check if user has access to this sale's warehouse
        if (!UserHelper::hasAccessToWarehouse($sale->warehouse_id)) {
            abort(403, 'You do not have permission to access this sale.');
        }

        return view('livewire.sales.print', [
            'sale' => $sale,
        ]);
    }
} 