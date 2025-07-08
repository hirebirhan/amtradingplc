<?php

namespace Database\Factories;

use App\Models\Branch;
use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Customer>
 */
class CustomerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->company(),
            'email' => $this->faker->unique()->safeEmail(),
            'phone' => $this->faker->phoneNumber(),
            'address' => $this->faker->streetAddress(),
            'city' => $this->faker->city(),
            'state' => $this->faker->state(),
            'postal_code' => $this->faker->postcode(),
            'country' => $this->faker->country(),
            'credit_limit' => $this->faker->randomFloat(2, 0, 10000),
            'balance' => $this->faker->randomFloat(2, 0, 5000),
            'customer_type' => $this->faker->randomElement(['retail', 'wholesale', 'distributor']),
            'branch_id' => Branch::inRandomOrder()->first()?->id ?? Branch::factory(),
            'is_active' => $this->faker->boolean(90), // 90% chance to be active
            'notes' => $this->faker->optional(0.7)->paragraph(),
        ];
    }

    /**
     * Configure the model factory.
     */
    public function configure()
    {
        return $this->afterMaking(function (Customer $customer) {
            // Additional logic after making the model if needed
        })->afterCreating(function (Customer $customer) {
            // Additional logic after creating the model if needed
        });
    }

    /**
     * Indicate that the customer is retail.
     */
    public function retail(): static
    {
        return $this->state(fn (array $attributes) => [
            'customer_type' => 'retail',
        ]);
    }

    /**
     * Indicate that the customer is wholesale.
     */
    public function wholesale(): static
    {
        return $this->state(fn (array $attributes) => [
            'customer_type' => 'wholesale',
        ]);
    }

    /**
     * Indicate that the customer is a distributor.
     */
    public function distributor(): static
    {
        return $this->state(fn (array $attributes) => [
            'customer_type' => 'distributor',
        ]);
    }

    /**
     * Indicate that the customer is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}