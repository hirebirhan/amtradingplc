<?php

namespace App\Services;

use App\Enums\PaymentMethod;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class SaleService
{
    public function createSale(User $actor, array $data): Sale
    {
        return DB::transaction(function () use ($actor, $data) {
            $items = $data['items'];
            unset($data['items']);

            // Resolve branch/warehouse from actor if not provided
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
            $sale->processSale();

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
}
