<?php

namespace App\Services\Sales;

use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Warehouse;
use App\Models\Branch;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SaleFormService
{
    public function generateReferenceNumber(): string
    {
        $prefix = 'SALE-';
        $date = date('Ymd');
        $count = Sale::whereDate('created_at', today())->count() + 1;
        return $prefix . $date . '-' . str_pad((string)$count, 5, '0', STR_PAD_LEFT);
    }

    public function getDefaultFormData(): array
    {
        return [
            'sale_date' => date('Y-m-d'),
            'reference_no' => $this->generateReferenceNumber(),
            'customer_id' => '',
            'is_walking_customer' => false,
            'warehouse_id' => '',
            'branch_id' => '',
            'payment_method' => 'cash',
            'payment_status' => 'paid',
            'tax' => 0,
            'shipping' => 0,
            'transaction_number' => '',
            'advance_amount' => 0,
            'notes' => '',
        ];
    }

    public function resolveBranchId(array $form): ?int
    {
        if (!empty($form['branch_id'])) {
            return (int)$form['branch_id'];
        }
        
        if (!empty($form['warehouse_id'])) {
            $warehouse = Warehouse::with('branches')->find($form['warehouse_id']);
            if ($warehouse && $warehouse->branches->isNotEmpty()) {
                return (int)$warehouse->branches->first()->id;
            }
        }
        
        if (Auth::user()->branch_id) {
            return (int)Auth::user()->branch_id;
        }
        
        if (Auth::user()->warehouse_id) {
            $warehouse = Warehouse::with('branches')->find(Auth::user()->warehouse_id);
            if ($warehouse && $warehouse->branches->isNotEmpty()) {
                return (int)$warehouse->branches->first()->id;
            }
        }
        
        $firstBranch = Branch::where('is_active', true)->first();
        return $firstBranch ? (int)$firstBranch->id : null;
    }

    public function createSale(array $form, array $items, float $totalAmount, float $taxAmount, float $shippingAmount): Sale
    {
        $branchId = $this->resolveBranchId($form);
        
        if (!$branchId) {
            throw new \Exception('Unable to determine branch for this sale.');
        }

        $sale = new Sale();
        $sale->reference_no = $form['reference_no'];
        $sale->customer_id = $form['is_walking_customer'] ? null : $form['customer_id'];
        $sale->is_walking_customer = $form['is_walking_customer'];
        
        if (!empty($form['warehouse_id'])) {
            $sale->warehouse_id = $form['warehouse_id'];
            $sale->branch_id = null;
        } else {
            $sale->branch_id = $branchId;
            $sale->warehouse_id = null;
        }
        
        $sale->user_id = Auth::id();
        $sale->sale_date = $form['sale_date'];
        $sale->payment_method = $form['payment_method'];
        $sale->payment_status = $form['payment_status'];
        $sale->status = 'pending';
        $sale->tax = $taxAmount;
        $sale->shipping = $shippingAmount;
        $sale->discount = 0;
        $sale->total_amount = $totalAmount;
        $sale->notes = $form['notes'];

        $this->setPaymentFields($sale, $form, $totalAmount);
        $sale->save();

        $this->createSaleItems($sale, $items);
        $sale->processSale();

        return $sale;
    }

    private function setPaymentFields(Sale $sale, array $form, float $totalAmount): void
    {
        switch ($form['payment_method']) {
            case 'cash':
            case 'bank_transfer':
            case 'telebirr':
                $sale->paid_amount = $totalAmount;
                $sale->due_amount = 0;
                $sale->payment_status = 'paid';
                break;
            case 'credit_advance':
                $sale->paid_amount = $form['advance_amount'];
                $sale->advance_amount = $form['advance_amount'];
                $sale->due_amount = $totalAmount - $form['advance_amount'];
                $sale->payment_status = 'partial';
                break;
            case 'credit_full':
                $sale->paid_amount = 0;
                $sale->due_amount = $totalAmount;
                $sale->payment_status = 'due';
                break;
        }

        if (in_array($form['payment_method'], ['telebirr', 'bank_transfer'], true)) {
            $sale->transaction_number = $form['transaction_number'];
        }

        if ($form['payment_method'] === 'bank_transfer') {
            $sale->bank_account_id = $form['bank_account_id'] ?? null;
        }
    }

    private function createSaleItems(Sale $sale, array $items): void
    {
        foreach ($items as $item) {
            if (empty($item['item_id']) || $item['quantity'] <= 0 || $item['price'] <= 0) {
                throw new \Exception('Invalid item data');
            }
            
            $saleItem = new SaleItem();
            $saleItem->sale_id = $sale->id;
            $saleItem->item_id = $item['item_id'];
            $saleItem->quantity = $item['quantity'];
            $saleItem->sale_method = $item['sale_method'] ?? 'piece';
            $saleItem->unit_price = $item['price'];
            $saleItem->subtotal = $item['subtotal'];
            $saleItem->notes = $item['notes'] ?? null;
            $saleItem->save();
        }
    }
}