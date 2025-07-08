<?php

namespace App\Http\Controllers;

use App\Models\Transfer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TransferController extends Controller
{
    /**
     * Print transfer document
     */
    public function print(Transfer $transfer)
    {
        try {
            Log::info('Transfer print requested', [
                'transfer_id' => $transfer->id,
                'reference_code' => $transfer->reference_code,
                'user_id' => auth()->id()
            ]);

            // Load necessary relationships for printing
            $transfer->load([
                'transferItems.item',
                'sourceLocation',
                'destinationLocation',
                'createdBy'
            ]);

            return view('pdf.transfer', [
                'transfer' => $transfer
            ]);

        } catch (\Exception $e) {
            Log::error('Transfer print failed', [
                'transfer_id' => $transfer->id,
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);

            return back()->with('error', 'Failed to generate transfer document. Please try again.');
        }
    }
} 