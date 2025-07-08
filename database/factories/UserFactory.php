<?php

namespace Database\Factories;

use App\Models\Branch;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $branch = Branch::inRandomOrder()->first();

        // Either assign a warehouse from the same branch or leave it null
        $warehouse = null;
        if ($branch && fake()->boolean(70)) {
            $warehouse = Warehouse::where('branch_id', $branch->id)
                ->inRandomOrder()
                ->first();
        }

        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'branch_id' => $branch?->id,
            'warehouse_id' => $warehouse?->id,
            'phone' => fake()->phoneNumber(),
            'position' => fake()->randomElement([
                'Manager',
                'Assistant Manager',
                'Warehouse Operator',
                'Inventory Clerk',
                'Sales Representative',
                'Stock Supervisor',
            ]),
            'is_active' => fake()->boolean(90), // 90% chance to be active
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
