<?php

namespace App\Services\Sales;

use App\Models\Warehouse;
use App\Models\Branch;
use App\Models\BankAccount;
use Illuminate\Support\Facades\Auth;

class LocationService
{
    public function loadLocations(): array
    {
        $user = Auth::user();
        
        // SuperAdmin and GeneralManager see all locations
        if ($user->isSuperAdmin() || $user->isGeneralManager()) {
            return [
                'branches' => Branch::orderBy('name')->get(),
                'warehouses' => Warehouse::with('branches')->orderBy('name')->get()
            ];
        }
        // Branch Manager sees warehouses in their branch
        elseif ($user->isBranchManager() && $user->branch_id) {
            return [
                'branches' => Branch::where('id', $user->branch_id)->get(),
                'warehouses' => Warehouse::with('branches')->whereHas('branches', function($q) use ($user) {
                    $q->where('branches.id', $user->branch_id);
                })->orderBy('name')->get()
            ];
        }
        // Users not assigned to specific location see all
        elseif (!$user->branch_id && !$user->warehouse_id) {
            return [
                'branches' => Branch::orderBy('name')->get(),
                'warehouses' => Warehouse::with('branches')->orderBy('name')->get()
            ];
        }

        return ['branches' => collect([]), 'warehouses' => collect([])];
    }

    public function autoSetUserLocation(): array
    {
        $user = Auth::user();
        $form = [];
        
        if ($user->warehouse_id) {
            // Warehouse user - auto-select their warehouse
            $form['warehouse_id'] = $user->warehouse_id;
        } elseif ($user->isBranchManager() && $user->branch_id) {
            // Branch manager - auto-select first warehouse in their branch
            $branchWarehouse = Warehouse::whereHas('branches', function($q) use ($user) {
                $q->where('branches.id', $user->branch_id);
            })->first();
            
            if ($branchWarehouse) {
                $form['warehouse_id'] = $branchWarehouse->id;
            }
        } elseif ($user->branch_id) {
            // Other branch users - set branch
            $form['branch_id'] = $user->branch_id;
        } else {
            // Auto-select warehouse with most stock
            $warehouseWithStock = Warehouse::select('warehouses.*')
                ->join('stocks', 'warehouses.id', '=', 'stocks.warehouse_id')
                ->where('stocks.quantity', '>', 0)
                ->groupBy('warehouses.id')
                ->orderByRaw('SUM(stocks.quantity) DESC')
                ->first();
                
            if ($warehouseWithStock) {
                $form['warehouse_id'] = $warehouseWithStock->id;
            } else {
                // Fallback to first warehouse
                $firstWarehouse = Warehouse::first();
                if ($firstWarehouse) {
                    $form['warehouse_id'] = $firstWarehouse->id;
                }
            }
        }

        return $form;
    }

    public function loadBankAccounts(): \Illuminate\Support\Collection
    {
        try {
            return BankAccount::where('is_active', true)->orderBy('account_name')->get();
        } catch (\Exception $e) {
            return collect([]);
        }
    }
}