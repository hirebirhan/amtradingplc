<?php

namespace Database\Factories;

use App\Models\Item;
use App\Models\PriceHistory;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class PriceHistoryFactory extends Factory
{
    protected $model = PriceHistory::class;

    public function definition(): array
    {
        return [
            'item_id' => Item::factory(),
            'old_price' => $this->faker->randomFloat(2, 1, 100),
            'new_price' => $this->faker->randomFloat(2, 1, 100),
            'old_cost' => $this->faker->randomFloat(2, 1, 50),
            'new_cost' => $this->faker->randomFloat(2, 1, 50),
            'change_type' => $this->faker->randomElement(['manual', 'purchase', 'sale']),
            'user_id' => User::factory(),
            'notes' => $this->faker->sentence(),
        ];
    }
}