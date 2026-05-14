<?php

namespace Database\Factories;

use App\Models\Item;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;

class StockReservationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'item_id'        => Item::factory(),
            'location_type'  => 'warehouse',
            'location_id'    => Warehouse::factory(),
            'quantity'       => fake()->randomFloat(2, 1, 50),
            'reference_type' => 'transfer',
            'reference_id'   => fake()->randomNumber(4),
            'expires_at'     => now()->addHours(24),
            'created_by'     => User::factory(),
        ];
    }
}
