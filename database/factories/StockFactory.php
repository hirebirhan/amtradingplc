<?php

namespace Database\Factories;

use App\Models\Item;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Stock>
 */
class StockFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'warehouse_id' => Warehouse::factory(),
            'item_id' => Item::factory(),
            'quantity' => $this->faker->randomFloat(2, 5, 100),
        ];
    }

    /**
     * Set the quantity for this stock.
     */
    public function withQuantity(float $quantity): static
    {
        return $this->state(fn (array $attributes) => [
            'quantity' => $quantity,
        ]);
    }

    /**
     * Set the warehouse for this stock.
     */
    public function inWarehouse(Warehouse $warehouse): static
    {
        return $this->state(fn (array $attributes) => [
            'warehouse_id' => $warehouse->id,
        ]);
    }

    /**
     * Set the item for this stock.
     */
    public function forItem(Item $item): static
    {
        return $this->state(fn (array $attributes) => [
            'item_id' => $item->id,
        ]);
    }
}
