<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create categories for seeds, oils, and flour distribution business
        $categories = [
            [
                'name' => 'Seeds & Grains',
                'code' => 'CAT-SEEDS',
                'description' => 'Various types of seeds and grains for planting and consumption',
                'is_active' => true,
            ],
            [
                'name' => 'Cooking Oils',
                'code' => 'CAT-OILS',
                'description' => 'Edible oils for cooking and food preparation',
                'is_active' => true,
            ],
            [
                'name' => 'Flour & Meals',
                'code' => 'CAT-FLOUR',
                'description' => 'Various types of flour and ground meals',
                'is_active' => true,
            ],
            [
                'name' => 'Spices & Seasonings',
                'code' => 'CAT-SPICES',
                'description' => 'Spices, herbs, and seasoning products',
                'is_active' => true,
            ],
            [
                'name' => 'Bulk Commodities',
                'code' => 'CAT-BULK',
                'description' => 'Large quantity bulk commodities and raw materials',
                'is_active' => true,
            ],
        ];

        foreach ($categories as $category) {
            Category::create($category);
        }
    }
}
