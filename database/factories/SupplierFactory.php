<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class SupplierFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name'       => fake()->company(),
            'email'      => fake()->unique()->companyEmail(),
            'phone'      => fake()->phoneNumber(),
            'address'    => fake()->streetAddress(),
            'city'       => fake()->city(),
            'country'    => fake()->country(),
            'is_active'  => true,
        ];
    }
}
