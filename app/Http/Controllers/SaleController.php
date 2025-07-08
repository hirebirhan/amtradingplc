<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use Illuminate\Http\Request;

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

        return view('livewire.sales.print', [
            'sale' => $sale,
        ]);
    }
} 