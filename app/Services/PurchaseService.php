<?php

namespace App\Services;

use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Item;
use App\Models\Credit;
use App\Models\Stock;
use App\Models\StockHistory;
use App\Enums\PaymentMethod;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class PurchaseService
{
    public function canCreatePurchases(): bool
    {
        $user = Auth::user();
        /** @var \App\Models\User $user */
        
        // SuperAdmin and GeneralManager can create purchases
        if ($user->isSuperAdmin() || $user->isGeneralManager()) {
            return true;
        }
        
        // Sales users can create purchases for their assigned branch
        if ($user->isSales() && $user->branch_id) {
            return true;
        }
        
        // BranchManager can create purchases
        if ($user->isBranchManager()) {
            return true;
        }
        
        // WarehouseUser can create purchases for their assigned warehouse
        if ($user->isWarehouseUser() && $user->warehouse_id) {
            return true;
        }
        
        // Check if user has the purchases.create permission
        return $user->can('purchases.create');
    }
    public function createPurchase(array $formData, array $items, float $totalAmount, float $taxAmount): Purchase
    {
        return DB::transaction(function () use ($formData, $items, $totalAmount, $taxAmount) {
            $purchase = $this->createPurchaseRecord($formData, $totalAmount, $taxAmount);
            $this->createPurchaseItems($purchase, $items);
            $this->createCreditIfNeeded($purchase, $formData, $totalAmount);
            
            return $purchase;
        });
    }

    private function createPurchaseRecord(array $formData, float $totalAmount, float $taxAmount): Purchase
    {
        $branchId = (int)($formData['branch_id'] ?? 0);
        if ($branchId <= 0) {
            $branchId = Auth::user()->branch_id ?? 1;
        }

        $warehouseId = $this->resolveWarehouseIdForBranch($branchId);

        $purchase = new Purchase();
        $purchase->reference_no = $this->generateUniqueReferenceNumber();
        $purchase->user_id = Auth::id();
        $purchase->branch_id = $branchId;
        $purchase->supplier_id = $formData['supplier_id'];
        $purchase->warehouse_id = $warehouseId;
        $purchase->purchase_date = $formData['purchase_date'];
        $purchase->payment_method = $formData['payment_method'];
        $purchase->status = 'pending';
        $purchase->discount = 0;
        $purchase->tax = $taxAmount;
        $purchase->total_amount = $totalAmount;
        $purchase->notes = $formData['notes'];

        $this->setPaymentAmounts($purchase, $formData, $totalAmount);
        $this->setPaymentFields($purchase, $formData);

        $purchase->save();

        return $purchase;
    }

    private function createPurchaseItems(Purchase $purchase, array $items): void
    {
        foreach ($items as $item) {
            $itemId = intval($item['item_id'] ?? 0);
            $quantity = floatval($item['quantity'] ?? 0);
            $cost = floatval($item['cost'] ?? 0);
            $subtotal = floatval($item['subtotal'] ?? ($quantity * $cost));
            
            if ($itemId <= 0 || $quantity <= 0 || $cost <= 0) {
                continue;
            }
            
            $itemRecord = Item::find($itemId);
            if (!$itemRecord) {
                continue;
            }
            
            $purchaseItem = new PurchaseItem();
            $purchaseItem->purchase_id = $purchase->id;
            $purchaseItem->item_id = $itemId;
            $purchaseItem->quantity = $quantity;
            $purchaseItem->unit_cost = $cost;
            $purchaseItem->discount = 0;
            $purchaseItem->subtotal = $subtotal;
            
            if (!empty($item['notes'])) {
                $purchaseItem->notes = $item['notes'];
            }
            
            $purchaseItem->save();
            
            $this->updateStock($purchase->warehouse_id, $itemId, $quantity, $purchase->id);
            $this->updateItemCostPrice($itemRecord, $cost);
        }
    }

    private function createCreditIfNeeded(Purchase $purchase, array $formData, float $totalAmount): void
    {
        if (!in_array($formData['payment_method'], [PaymentMethod::FULL_CREDIT->value, PaymentMethod::CREDIT_ADVANCE->value], true)) {
            return;
        }

        if ($formData['payment_method'] === PaymentMethod::FULL_CREDIT->value) {
            Credit::create([
                'supplier_id' => $purchase->supplier_id,
                'amount' => $totalAmount,
                'paid_amount' => 0,
                'balance' => $totalAmount,
                'reference_no' => $purchase->reference_no,
                'reference_type' => 'purchase',
                'reference_id' => $purchase->id,
                'credit_type' => 'payable',
                'description' => 'Full credit for purchase #' . $purchase->reference_no,
                'credit_date' => $purchase->purchase_date,
                'due_date' => now()->addDays(30),
                'status' => 'active',
                'user_id' => Auth::id(),
                'branch_id' => $purchase->branch_id,
                'warehouse_id' => $purchase->warehouse_id,
            ]);
        } elseif ($formData['payment_method'] === PaymentMethod::CREDIT_ADVANCE->value) {
            $dueAmount = $totalAmount - $formData['advance_amount'];
            if ($dueAmount > 0) {
                Credit::create([
                    'supplier_id' => $purchase->supplier_id,
                    'amount' => $totalAmount,
                    'paid_amount' => $formData['advance_amount'],
                    'balance' => $dueAmount,
                    'reference_no' => $purchase->reference_no,
                    'reference_type' => 'purchase',
                    'reference_id' => $purchase->id,
                    'credit_type' => 'payable',
                    'description' => 'Credit with advance for purchase #' . $purchase->reference_no,
                    'credit_date' => $purchase->purchase_date,
                    'due_date' => now()->addDays(30),
                    'status' => 'partial',
                    'user_id' => Auth::id(),
                    'branch_id' => $purchase->branch_id,
                    'warehouse_id' => $purchase->warehouse_id,
                ]);
            }
        }
    }

    private function updateStock($warehouseId, $itemId, $quantity, $purchaseId = null): void
    {
        $item = Item::find($itemId);
        if (!$item) {
            throw new \Exception("Item not found: {$itemId}");
        }
        
        $unitCapacity = $item->unit_quantity ?? 1;
        
        $stock = Stock::firstOrCreate(
            [
                'warehouse_id' => $warehouseId,
                'item_id' => $itemId
            ],
            [
                'quantity' => 0,
                'piece_count' => 0,
                'total_units' => 0,
                'current_piece_units' => $unitCapacity,
                'created_by' => Auth::id()
            ]
        );
        
        $originalPieces = $stock->piece_count ?? 0;
        $originalQuantity = $stock->quantity ?? 0;
        $originalUnits = $stock->total_units ?? 0;
        
        $addedPieces = (int)$quantity;
        $stock->piece_count = $originalPieces + $addedPieces;
        $stock->quantity = $stock->piece_count;
        $stock->total_units = $originalUnits + ($addedPieces * $unitCapacity);
        $stock->updated_by = Auth::id();
        
        if ($stock->current_piece_units === null) {
            $stock->current_piece_units = $unitCapacity;
        }
        
        $stock->save();
        
        StockHistory::create([
            'warehouse_id' => $warehouseId,
            'item_id' => $itemId,
            'quantity_before' => $originalQuantity,
            'quantity_after' => $stock->quantity,
            'quantity_change' => $addedPieces,
            'units_before' => $originalUnits,
            'units_after' => $stock->total_units,
            'units_change' => ($addedPieces * $unitCapacity),
            'reference_type' => 'purchase',
            'reference_id' => $purchaseId,
            'description' => 'Stock added from purchase',
            'user_id' => Auth::id(),
        ]);
    }

    private function updateItemCostPrice(Item $item, float $cost): void
    {
        if ($cost > 0) {
            $unitQuantity = $item->unit_quantity ?? 1;
            $costPerUnit = $cost / $unitQuantity;
            
            $item->cost_price = $cost;
            $item->cost_price_per_unit = $costPerUnit;
            $item->save();
        }
    }

    private function setPaymentAmounts(Purchase $purchase, array $formData, float $totalAmount): void
    {
        if ($formData['payment_method'] === PaymentMethod::FULL_CREDIT->value) {
            $purchase->paid_amount = 0;
            $purchase->due_amount = $totalAmount;
            $purchase->payment_status = 'due';
        } elseif ($formData['payment_method'] === PaymentMethod::CREDIT_ADVANCE->value) {
            $purchase->paid_amount = $formData['advance_amount'];
            $purchase->due_amount = $totalAmount - $formData['advance_amount'];
            $purchase->payment_status = 'partial';
        } else {
            $purchase->paid_amount = $totalAmount;
            $purchase->due_amount = 0;
            $purchase->payment_status = 'paid';
        }
    }

    private function setPaymentFields(Purchase $purchase, array $formData): void
    {
        if (in_array($formData['payment_method'], [PaymentMethod::BANK_TRANSFER->value, PaymentMethod::TELEBIRR->value], true)) {
            $purchase->transaction_number = $formData['transaction_number'] ?? null;
        }

        if ($formData['payment_method'] === PaymentMethod::BANK_TRANSFER->value) {
            $purchase->bank_account_id = $formData['bank_account_id'] ?? null;
        }
    }

    private function resolveWarehouseIdForBranch(int $branchId): int
    {
        $branch = \App\Models\Branch::with('warehouses')->find($branchId);
        if ($branch && $branch->warehouses->isNotEmpty()) {
            return (int)$branch->warehouses->first()->id;
        }
        
        $code = 'WH-BR-' . $branchId;
        $name = 'Default Warehouse - ' . ($branch?->name ?? ('Branch ' . $branchId));
        $warehouse = \App\Models\Warehouse::firstOrCreate(
            ['code' => $code],
            [
                'name' => $name,
                'address' => $branch?->address,
            ]
        );
        $warehouse->branches()->syncWithoutDetaching([$branchId]);
        return (int)$warehouse->id;
    }

    private function generateUniqueReferenceNumber(): string
    {
        do {
            $datePrefix = 'PO-' . now()->format('Ymd');
            $microtime = str_replace('.', '', microtime(true));
            $randomBytes = bin2hex(random_bytes(6));
            $userId = Auth::id() ?? 0;
            $randomNumber = mt_rand(1000, 9999);
            
            $unique = substr($microtime . $randomBytes . $userId . $randomNumber, 0, 12);
            $referenceNo = $datePrefix . '-' . $unique;
            
            $exists = Purchase::where('reference_no', $referenceNo)->exists();
            
        } while ($exists);
        
        return $referenceNo;
    }
}