<?php

namespace Database\Factories;

use App\Models\Item;
use App\Models\Purchase;
use Illuminate\Database\Eloquent\Factories\Factory;

class PurchaseItemFactory extends Factory
{
    public function definition(): array
    {
        $qty      = fake()->randomFloat(2, 1, 100);
        $cost     = fake()->randomFloat(2, 10, 500);
        $subtotal = round($qty * $cost, 2);

        return [
            'purchase_id' => Purchase::factory(),
            'item_id'     => Item::factory(),
            'quantity'    => $qty,
            'unit_cost'   => $cost,
            'subtotal'    => $subtotal,
        ];
    }
}
