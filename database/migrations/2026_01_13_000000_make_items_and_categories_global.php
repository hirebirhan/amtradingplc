<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * This migration makes items and categories global across all branches.
     * Stock data remains branch-specific through warehouse relationships.
     */
    public function up(): void
    {
        // Set all existing categories to be global (branch_id = NULL)
        DB::table('categories')->update(['branch_id' => null]);
        
        // Set all existing items to be global (branch_id = NULL)
        DB::table('items')->update(['branch_id' => null]);
        
        // Update unique constraints to be global instead of per-branch
        Schema::table('categories', function (Blueprint $table) {
            // Drop existing branch-specific unique constraints
            $table->dropUnique('categories_branch_code_unique');
            
            // Add global unique constraints
            $table->unique('code', 'categories_code_unique');
            $table->unique('slug', 'categories_slug_unique');
        });
        
        Schema::table('items', function (Blueprint $table) {
            // Drop existing branch-specific unique constraints
            $table->dropUnique('items_branch_name_unique');
            $table->dropUnique('items_branch_sku_unique');
            
            // Add global unique constraints
            $table->unique('name', 'items_name_unique');
            $table->unique('sku', 'items_sku_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // This migration is not easily reversible as it would require
        // re-assigning branch_id values to items and categories
        // which we don't have a reliable way to determine
        
        Schema::table('items', function (Blueprint $table) {
            $table->dropUnique('items_name_unique');
            $table->dropUnique('items_sku_unique');
        });
        
        Schema::table('categories', function (Blueprint $table) {
            $table->dropUnique('categories_code_unique');
            $table->dropUnique('categories_slug_unique');
        });
    }
};
