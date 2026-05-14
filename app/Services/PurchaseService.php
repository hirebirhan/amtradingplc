<?php

namespace App\Services;

use App\Models\Credit;
use App\Models\Item;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Stock;
use App\Models\StockHistory;
use App\Models\User;
use App\Enums\PaymentMethod;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PurchaseService
{
    public function canCreatePurchases(?User $actor = null): bool
    {
        $user = $actor ?? Auth::user();
        if (! $user) {
            return false;
        }

        if ($user->isSuperAdmin() || $user->isGeneralManager()) {
            return true;
        }
        if ($user->isSales() && $user->branch_id) {
            return true;
        }
        if ($user->isBranchManager()) {
            return true;
        }
        if ($user->isWarehouseUser() && $user->warehouse_id) {
            return true;
        }

        return $user->can('purchases.create');
    }

    public function createPurchase(User $actor, array $formData, array $items, float $totalAmount, float $taxAmount): Purchase
    {
        return DB::transaction(function () use ($actor, $formData, $items, $totalAmount, $taxAmount) {
            $purchase = $this->createPurchaseRecord($actor, $formData, $totalAmount, $taxAmount);
            $this->createPurchaseItems($actor, $purchase, $items);
            $this->createCreditIfNeeded($actor, $purchase, $formData, $totalAmount);
            $this->autoReceivePurchase($actor, $purchase);
            return $purchase;
        });
    }

    private function createPurchaseRecord(User $actor, array $formData, float $totalAmount, float $taxAmount): Purchase
    {
        $branchId = (int) ($formData['branch_id'] ?? $actor->branch_id ?? 0);
        if ($branchId <= 0) {
            throw new \DomainException('A branch must be specified for this purchase.');
        }

        $warehouseId = (int) ($formData['warehouse_id'] ?? 0);
        if ($warehouseId <= 0) {
            $warehouseId = $this->resolveWarehouseIdForBranch($branchId);
        }

        $purchase = new Purchase();
        $purchase->reference_no   = $this->generateUniqueReferenceNumber();
        $purchase->user_id        = $actor->id;
        $purchase->created_by     = $actor->id;
        $purchase->branch_id      = $branchId;
        $purchase->supplier_id    = $formData['supplier_id'];
        $purchase->warehouse_id   = $warehouseId;
        $purchase->purchase_date  = $formData['purchase_date'];
        $purchase->payment_method = $formData['payment_method'];
        $purchase->status         = \App\Enums\PurchaseStatus::RECEIVED->value;
        $purchase->discount       = 0;
        $purchase->tax            = $taxAmount;
        $purchase->total_amount   = $totalAmount;
        $purchase->notes          = $formData['notes'] ?? null;

        $this->setPaymentAmounts($purchase, $formData, $totalAmount);
        $this->setPaymentFields($purchase, $formData);

        $purchase->save();
        return $purchase;
    }

    private function createPurchaseItems(User $actor, Purchase $purchase, array $items): void
    {
        foreach ($items as $item) {
            $itemId   = (int) ($item['item_id'] ?? 0);
            $quantity = (float) ($item['quantity'] ?? 0);
            $cost     = (float) ($item['unit_cost'] ?? $item['cost'] ?? 0);
            $subtotal = (float) ($item['subtotal'] ?? ($quantity * $cost));

            if ($itemId <= 0 || $quantity <= 0 || $cost <= 0) {
                continue;
            }

            $itemRecord = Item::find($itemId);
            if (! $itemRecord) {
                continue;
            }

            $purchaseItem              = new PurchaseItem();
            $purchaseItem->purchase_id = $purchase->id;
            $purchaseItem->item_id     = $itemId;
            $purchaseItem->quantity    = $quantity;
            $purchaseItem->unit_cost   = $cost;
            $purchaseItem->discount    = 0;
            $purchaseItem->subtotal    = $subtotal;

            if (! empty($item['notes'])) {
                $purchaseItem->notes = $item['notes'];
            }

            $purchaseItem->save();

            $this->updateStock($actor, $purchase->warehouse_id, $itemId, $quantity, $purchase->id);
            $this->updateItemCostPrice($itemRecord, $cost);
        }
    }

    private function createCreditIfNeeded(User $actor, Purchase $purchase, array $formData, float $totalAmount): void
    {
        if (! in_array($formData['payment_method'], [PaymentMethod::FULL_CREDIT->value, PaymentMethod::CREDIT_ADVANCE->value], true)) {
            return;
        }

        $base = [
            'supplier_id'    => $purchase->supplier_id,
            'amount'         => $totalAmount,
            'reference_no'   => $purchase->reference_no,
            'reference_type' => 'purchase',
            'reference_id'   => $purchase->id,
            'credit_type'    => 'payable',
            'credit_date'    => $purchase->purchase_date,
            'due_date'       => now()->addDays(30),
            'user_id'        => $actor->id,
            'branch_id'      => $purchase->branch_id,
            'warehouse_id'   => $purchase->warehouse_id,
        ];

        if ($formData['payment_method'] === PaymentMethod::FULL_CREDIT->value) {
            Credit::create(array_merge($base, [
                'paid_amount' => 0,
                'balance'     => $totalAmount,
                'description' => 'Full credit for purchase #' . $purchase->reference_no,
                'status'      => 'active',
            ]));
        } elseif ($formData['payment_method'] === PaymentMethod::CREDIT_ADVANCE->value) {
            $advance  = (float) ($formData['advance_amount'] ?? 0);
            $dueAmount = $totalAmount - $advance;
            if ($dueAmount > 0) {
                Credit::create(array_merge($base, [
                    'paid_amount' => $advance,
                    'balance'     => $dueAmount,
                    'description' => 'Credit with advance for purchase #' . $purchase->reference_no,
                    'status'      => $advance > 0 ? 'partial' : 'active',
                ]));
            }
        }
    }

    private function updateStock(User $actor, int $warehouseId, int $itemId, float $quantity, ?int $purchaseId = null): void
    {
        $item = Item::find($itemId);
        if (! $item) {
            throw new \Exception("Item not found: {$itemId}");
        }

        $unitCapacity = $item->unit_quantity ?? 1;

        $stock = Stock::firstOrCreate(
            ['warehouse_id' => $warehouseId, 'item_id' => $itemId],
            ['quantity' => 0, 'piece_count' => 0, 'total_units' => 0, 'current_piece_units' => $unitCapacity, 'created_by' => $actor->id]
        );

        $originalPieces   = (int) ($stock->piece_count ?? 0);
        $originalQuantity = (float) ($stock->quantity ?? 0);
        $originalUnits    = (float) ($stock->total_units ?? 0);
        $addedPieces      = (int) $quantity;

        $stock->piece_count         = $originalPieces + $addedPieces;
        $stock->quantity            = $stock->piece_count;
        $stock->total_units         = $originalUnits + ($addedPieces * $unitCapacity);
        $stock->updated_by          = $actor->id;
        $stock->current_piece_units ??= $unitCapacity;
        $stock->save();

        StockHistory::create([
            'warehouse_id'    => $warehouseId,
            'item_id'         => $itemId,
            'quantity_before' => $originalQuantity,
            'quantity_after'  => $stock->quantity,
            'quantity_change' => $addedPieces,
            'reference_type'  => 'purchase',
            'reference_id'    => $purchaseId,
            'description'     => 'Stock added from purchase',
            'user_id'         => $actor->id,
        ]);
    }

    private function updateItemCostPrice(Item $item, float $cost): void
    {
        if ($cost > 0) {
            $item->cost_price          = $cost;
            $item->cost_price_per_unit = $cost / ($item->unit_quantity ?? 1);
            $item->save();
        }
    }

    private function setPaymentAmounts(Purchase $purchase, array $formData, float $totalAmount): void
    {
        if ($formData['payment_method'] === PaymentMethod::FULL_CREDIT->value) {
            $purchase->paid_amount    = 0;
            $purchase->due_amount     = $totalAmount;
            $purchase->payment_status = 'due';
        } elseif ($formData['payment_method'] === PaymentMethod::CREDIT_ADVANCE->value) {
            $advance                  = (float) ($formData['advance_amount'] ?? 0);
            $purchase->paid_amount    = $advance;
            $purchase->due_amount     = $totalAmount - $advance;
            $purchase->advance_amount = $advance;
            $purchase->payment_status = 'partial';
        } else {
            $purchase->paid_amount    = $totalAmount;
            $purchase->due_amount     = 0;
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
            return (int) $branch->warehouses->first()->id;
        }

        $code      = 'WH-BR-' . $branchId;
        $name      = 'Default Warehouse - ' . ($branch?->name ?? ('Branch ' . $branchId));
        $warehouse = \App\Models\Warehouse::firstOrCreate(
            ['code' => $code],
            ['name' => $name, 'address' => $branch?->address]
        );
        $warehouse->branches()->syncWithoutDetaching([$branchId]);
        return (int) $warehouse->id;
    }

    private function autoReceivePurchase(User $actor, Purchase $purchase): void
    {
        \Log::info('Purchase auto-received', [
            'purchase_id'  => $purchase->id,
            'reference_no' => $purchase->reference_no,
            'items_count'  => $purchase->items->count(),
            'user_id'      => $actor->id,
        ]);
    }

    private function generateUniqueReferenceNumber(): string
    {
        do {
            $datePrefix  = 'PO-' . now()->format('Ymd');
            $random      = strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
            $referenceNo = $datePrefix . '-' . $random;
        } while (Purchase::where('reference_no', $referenceNo)->exists());

        return $referenceNo;
    }
}
