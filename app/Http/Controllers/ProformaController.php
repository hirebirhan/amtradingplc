<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Proforma;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProformaController extends Controller
{
    public function print(Proforma $proforma, Request $request): View
    {
        $proforma->load(['customer', 'warehouse', 'branch', 'user', 'items.item']);
        
        $includeVat = $request->get('vat', '1') === '1';
        
        return view('proforma-print', compact('proforma', 'includeVat'));
    }

    public function pdf(Proforma $proforma)
    {
        $proforma->load(['customer', 'warehouse', 'branch', 'user', 'items.item']);
        
        // PDF generation logic here if needed
        return $this->print($proforma);
    }
}