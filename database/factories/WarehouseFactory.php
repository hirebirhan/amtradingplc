<?php

namespace Database\Factories;

use App\Models\Branch;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Warehouse>
 */
class WarehouseFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->words(2, true) . ' Warehouse',
            'code' => strtoupper($this->faker->unique()->bothify('WH#??')),
            'address' => $this->faker->address(),
            'manager_name' => $this->faker->name(),
            'phone' => $this->faker->phoneNumber(),
        ];
    }
}
