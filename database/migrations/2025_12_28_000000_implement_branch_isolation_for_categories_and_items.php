<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add branch_id to categories table
        Schema::table('categories', function (Blueprint $table) {
            $table->unsignedBigInteger('branch_id')->nullable()->after('id');
            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('cascade');
            
            // Drop existing unique constraint on code
            $table->dropUnique(['code']);
            
            // Add composite unique constraint for branch isolation with soft delete support
            $table->unique(['branch_id', 'code', 'deleted_at'], 'categories_branch_code_unique');
            $table->index(['branch_id', 'is_active', 'deleted_at'], 'idx_categories_branch_active');
        });

        // Update items table for better branch isolation
        Schema::table('items', function (Blueprint $table) {
            // Drop existing constraint and add new one with soft delete support
            $table->dropUnique('items_name_branch_unique');
            $table->unique(['branch_id', 'name', 'deleted_at'], 'items_branch_name_unique');
            $table->unique(['branch_id', 'sku', 'deleted_at'], 'items_branch_sku_unique');
            
            // Add performance indexes
            $table->index(['branch_id', 'category_id', 'deleted_at'], 'idx_items_branch_category');
            $table->index(['branch_id', 'is_active', 'deleted_at'], 'idx_items_branch_active');
        });
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropIndex('idx_categories_branch_active');
            $table->dropUnique('categories_branch_code_unique');
            $table->dropForeign(['branch_id']);
            $table->dropColumn('branch_id');
            $table->unique('code');
        });

        Schema::table('items', function (Blueprint $table) {
            $table->dropIndex('idx_items_branch_category');
            $table->dropIndex('idx_items_branch_active');
            $table->dropUnique('items_branch_sku_unique');
            $table->dropUnique('items_branch_name_unique');
            $table->unique(['name', 'branch_id'], 'items_name_branch_unique');
        });
    }
};