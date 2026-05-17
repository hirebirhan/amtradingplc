<?php

namespace App\Services;

use App\Enums\PaymentMethod;
use App\Enums\SaleStatus;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Stock;
use App\Models\StockHistory;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;

class SaleService
{
    public function createSale(User $actor, array $data): Sale
    {
        return DB::transaction(function () use ($actor, $data) {
            $items = $data['items'];
            unset($data['items']);

            $branchId    = (int) ($data['branch_id']    ?? $actor->branch_id    ?? 0) ?: null;
            $warehouseId = (int) ($data['warehouse_id'] ?? $actor->warehouse_id ?? 0) ?: null;

            $total   = collect($items)->sum(fn ($i) => (float) ($i['subtotal'] ?? ((float) $i['quantity'] * (float) $i['unit_price'])));
            $advance = (float) ($data['advance_amount'] ?? 0);

            [$paidAmount, $dueAmount, $paymentStatus] = $this->resolveAmounts($data['payment_method'], $total, $advance);

            $sale = Sale::create([
                'customer_id'        => $data['customer_id'] ?? null,
                'is_walking_customer' => $data['is_walking_customer'] ?? false,
                'warehouse_id'       => $warehouseId,
                'branch_id'          => $branchId,
                'user_id'            => $actor->id,
                'created_by'         => $actor->id,
                'payment_method'     => $data['payment_method'],
                'payment_status'     => $paymentStatus,
                'transaction_number' => $data['transaction_number'] ?? null,
                'bank_account_id'    => $data['bank_account_id']    ?? null,
                'advance_amount'     => $advance,
                'total_amount'       => $total,
                'paid_amount'        => $paidAmount,
                'due_amount'         => $dueAmount,
                'discount'           => (float) ($data['discount']  ?? 0),
                'tax'                => (float) ($data['tax']       ?? 0),
                'shipping'           => (float) ($data['shipping']  ?? 0),
                'sale_date'          => $data['sale_date'] ?? now()->toDateString(),
                'notes'              => $data['notes'] ?? null,
                'status'             => 'pending',
            ]);

            foreach ($items as $itemData) {
                SaleItem::create([
                    'sale_id'     => $sale->id,
                    'item_id'     => $itemData['item_id'],
                    'quantity'    => $itemData['quantity'],
                    'unit_price'  => $itemData['unit_price'],
                    'sale_method' => $itemData['sale_method'] ?? 'piece',
                    'subtotal'    => $itemData['subtotal'] ?? ((float) $itemData['quantity'] * (float) $itemData['unit_price']),
                    'discount'    => (float) ($itemData['discount'] ?? 0),
                    'notes'       => $itemData['notes'] ?? null,
                ]);
            }

            $sale->load('items.item');
            $this->processStockForSale($sale);

            return $sale->fresh();
        });
    }

    private function resolveAmounts(string $method, float $total, float $advance): array
    {
        return match ($method) {
            PaymentMethod::FULL_CREDIT->value    => [0, $total, 'due'],
            PaymentMethod::CREDIT_ADVANCE->value => [$advance, $total - $advance, 'partial'],
            default                              => [$total,  0,     'paid'],
        };
    }

    private function processStockForSale(Sale $sale): void
    {
        if ($sale->status === SaleStatus::COMPLETED->value) {
            return;
        }

        foreach ($sale->items as $saleItem) {
            $item = $saleItem->item;
            if ($sale->warehouse_id) {
                $this->processWarehouseSaleItem($sale, $item, $saleItem);
            } else {
                $this->processBranchSaleItem($sale, $item, $saleItem);
            }
        }

        $sale->status = SaleStatus::COMPLETED->value;
        $sale->save();

        if (in_array($sale->payment_method, [PaymentMethod::FULL_CREDIT->value, PaymentMethod::CREDIT_ADVANCE->value], true)) {
            $sale->createCreditRecord();
        }
    }

    private function processWarehouseSaleItem(Sale $sale, $item, $saleItem): void
    {
        $warehouse = Warehouse::with('branches')->find($sale->warehouse_id);
        if (! $warehouse) {
            throw new \Exception('Warehouse not found: '.$sale->warehouse_id);
        }

        $warehouseBranchId = $warehouse->branches->first()?->id;
        if ($sale->branch_id && $warehouseBranchId !== $sale->branch_id) {
            throw new \Exception('Branch isolation violation: Warehouse does not belong to sale branch');
        }

        $stock = Stock::where('warehouse_id', $sale->warehouse_id)
            ->where('item_id', $item->id)
            ->lockForUpdate()
            ->first();

        if (! $stock) {
            $stock = Stock::create([
                'warehouse_id' => $sale->warehouse_id,
                'item_id'      => $item->id,
                'branch_id'    => $sale->branch_id,
                'quantity'     => 0,
                'piece_count'  => 0,
                'total_units'  => 0,
                'created_by'   => $sale->user_id,
            ]);
        }

        $quantity = $saleItem->quantity;
        if ($saleItem->isSoldByUnit()) {
            $quantity = $quantity / max($item->unit_quantity ?? 1, 1);
        }

        $quantityBefore    = $stock->quantity;
        $stock->quantity   -= $quantity;
        $stock->piece_count -= $quantity;
        $stock->save();

        StockHistory::create([
            'item_id'         => $item->id,
            'warehouse_id'    => $sale->warehouse_id,
            'branch_id'       => $sale->branch_id,
            'movement_type'   => 'sale',
            'quantity_change' => -$quantity,
            'quantity_before' => $quantityBefore,
            'quantity_after'  => $stock->quantity,
            'reference_id'    => $sale->id,
            'reference_type'  => 'sale',
            'notes'           => 'Sale #'.$sale->reference_no,
            'created_by'      => $sale->user_id,
        ]);
    }

    private function processBranchSaleItem(Sale $sale, $item, $saleItem): void
    {
        $stocks = Stock::where('item_id', $item->id)
            ->where('branch_id', $sale->branch_id)
            ->orderBy('quantity', 'desc')
            ->lockForUpdate()
            ->get();

        if ($stocks->isEmpty()) {
            $firstWarehouse = Warehouse::whereHas('branches', fn ($q) => $q->where('branches.id', $sale->branch_id))->first();
            if (! $firstWarehouse) {
                throw new \Exception('No warehouses found for branch: '.$sale->branch_id);
            }
            $stocks = collect([Stock::create([
                'warehouse_id' => $firstWarehouse->id,
                'item_id'      => $item->id,
                'branch_id'    => $sale->branch_id,
                'quantity'     => 0,
                'piece_count'  => 0,
                'total_units'  => 0,
                'created_by'   => $sale->user_id,
            ])]);
        }

        $quantity = $saleItem->quantity;
        if ($saleItem->isSoldByUnit()) {
            $quantity = $quantity / max($item->unit_quantity ?? 1, 1);
        }

        $remainingQuantity = $quantity;

        foreach ($stocks as $stock) {
            if ($remainingQuantity <= 0) {
                break;
            }

            $deductQuantity = $stock->quantity > 0
                ? min($remainingQuantity, $stock->quantity)
                : $remainingQuantity;

            $quantityBefore    = $stock->quantity;
            $stock->quantity   -= $deductQuantity;
            $stock->piece_count -= $deductQuantity;
            $stock->save();

            StockHistory::create([
                'item_id'         => $item->id,
                'warehouse_id'    => $stock->warehouse_id,
                'branch_id'       => $sale->branch_id,
                'movement_type'   => 'sale',
                'quantity_change' => -$deductQuantity,
                'quantity_before' => $quantityBefore,
                'quantity_after'  => $stock->quantity,
                'reference_id'    => $sale->id,
                'reference_type'  => 'sale',
                'notes'           => 'Branch sale #'.$sale->reference_no,
                'created_by'      => $sale->user_id,
            ]);

            $remainingQuantity -= $deductQuantity;
        }
    }
}
