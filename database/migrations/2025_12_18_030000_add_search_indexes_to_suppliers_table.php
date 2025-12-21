<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            // Add composite index for search optimization
            $table->index(['name', 'email', 'phone'], 'suppliers_search_idx');
            
            // Add individual indexes for frequently searched fields
            $table->index('reference_no', 'suppliers_reference_idx');
            $table->index('is_active', 'suppliers_active_idx');
            $table->index(['branch_id', 'is_active'], 'suppliers_branch_active_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            $table->dropIndex('suppliers_search_idx');
            $table->dropIndex('suppliers_reference_idx');
            $table->dropIndex('suppliers_active_idx');
            $table->dropIndex('suppliers_branch_active_idx');
        });
    }
};