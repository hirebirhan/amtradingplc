<?php

namespace App\Livewire\Purchases\Traits;

use App\Models\Supplier;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

trait HandlesSuppliers
{
    public $suppliers = [];
    public $selectedSupplier = null;
    public $supplierSearch = '';

    public function selectSupplier($supplierId)
    {
        $supplier = Supplier::find($supplierId);
        if ($supplier) {
            $this->selectedSupplier = [
                'id' => $supplier->id,
                'name' => $supplier->name,
                'phone' => $supplier->phone
            ];
            $this->form['supplier_id'] = $supplierId;
            $this->supplierSearch = '';
            $this->resetErrorBag('form.supplier_id');
        }
    }

    public function clearSupplier()
    {
        $this->selectedSupplier = null;
        $this->form['supplier_id'] = '';
    }

    public function getFilteredSupplierOptionsProperty()
    {
        if (empty($this->supplierSearch)) {
            return [];
        }

        return $this->suppliers
            ->filter(function ($supplier) {
                return stripos($supplier->name, $this->supplierSearch) !== false ||
                       stripos($supplier->phone, $this->supplierSearch) !== false ||
                       stripos($supplier->email, $this->supplierSearch) !== false;
            })
            ->take(5)
            ->map(function ($supplier) {
                return [
                    'id' => $supplier->id,
                    'name' => $supplier->name,
                    'phone' => $supplier->phone
                ];
            })
            ->toArray();
    }

    public function supplierSelected($supplier)
    {
        if (!$supplier) {
            $this->form['supplier_id'] = '';
            return;
        }

        if (is_array($supplier) && isset($supplier['id'])) {
            $this->form['supplier_id'] = (int)$supplier['id'];
            $this->selectedSupplier = $supplier;
            
            $this->dispatch('createHiddenSupplierField', [
                'id' => $this->form['supplier_id'],
                'name' => $supplier['name'] ?? 'unknown'
            ]);
        } elseif (is_numeric($supplier)) {
            $this->form['supplier_id'] = (int)$supplier;
            
            $supplierModel = Supplier::find($this->form['supplier_id']);
            if ($supplierModel) {
                $this->selectedSupplier = [
                    'id' => $supplierModel->id,
                    'name' => $supplierModel->name,
                    'phone' => $supplierModel->phone
                ];
            }
            
            $supplierName = $supplierModel ? $supplierModel->name : 'unknown';
            
            $this->dispatch('createHiddenSupplierField', [
                'id' => $this->form['supplier_id'],
                'name' => $supplierName
            ]);
        }
    }

    public function updatedFormSupplierId($value)
    {
        $this->form['supplier_id'] = (int)$value;
        $this->resetErrorBag('form.supplier_id');
        
        if (!empty($this->form['supplier_id'])) {
            $supplier = $this->suppliers->firstWhere('id', $this->form['supplier_id']);
            if ($supplier) {
                $this->selectedSupplier = [
                    'id' => $supplier->id,
                    'name' => $supplier->name
                ];
            }
        } else {
            $this->selectedSupplier = null;
        }
    }

    public function getFilteredSuppliersProperty()
    {
        // Always return all suppliers for the dropdown
        return $this->suppliers;
    }
}