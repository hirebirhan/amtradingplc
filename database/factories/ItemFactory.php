<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Item>
 */
class ItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $costPrice = $this->faker->randomFloat(2, 10, 5000);
        $sellingPrice = $costPrice * (1 + $this->faker->randomFloat(2, 0.1, 0.5)); // 10-50% markup

        return [
            'name' => $this->faker->unique()->words(3, true),
            'sku' => strtoupper($this->faker->unique()->bothify('####')),
            'barcode' => $this->faker->unique()->ean13(),
            'category_id' => Category::factory(),
            'cost_price' => $costPrice,
            'selling_price' => $sellingPrice,
            'reorder_level' => $this->faker->numberBetween(5, 50),
            'unit' => $this->faker->randomElement(['PCS', 'KG', 'LTR', 'BOX', 'CTN']),
            'brand' => $this->faker->company(),
            'description' => $this->faker->paragraph(),
            'image_path' => null,
            'is_active' => true,
        ];
    }

    /**
     * Indicate that the item is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Set the category for this item.
     */
    public function inCategory(Category $category): static
    {
        return $this->state(fn (array $attributes) => [
            'category_id' => $category->id,
        ]);
    }
}
