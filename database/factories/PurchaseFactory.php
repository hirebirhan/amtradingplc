<?php

namespace Database\Factories;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\PurchaseStatus;
use App\Models\Branch;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class PurchaseFactory extends Factory
{
    public function definition(): array
    {
        $total = fake()->randomFloat(2, 100, 10000);

        return [
            'reference_no'   => 'PO-' . strtoupper(Str::random(8)),
            'supplier_id'    => Supplier::factory(),
            'branch_id'      => Branch::factory(),
            'warehouse_id'   => Warehouse::factory(),
            'user_id'        => User::factory(),
            'status'         => PurchaseStatus::CONFIRMED->value,
            'payment_status' => PaymentStatus::DUE->value,
            'payment_method' => PaymentMethod::CASH->value,
            'total_amount'   => $total,
            'paid_amount'    => 0,
            'due_amount'     => $total,
            'advance_amount' => 0,
            'purchase_date'  => now()->toDateString(),
        ];
    }
}
