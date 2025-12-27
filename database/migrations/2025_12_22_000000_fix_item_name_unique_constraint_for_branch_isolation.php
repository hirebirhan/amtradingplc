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
        Schema::table('items', function (Blueprint $table) {
            // Drop the global unique constraint on name
            $table->dropUnique(['name']);
            
            // Add composite unique constraint: name + branch_id
            // This allows same item names across different branches
            $table->unique(['name', 'branch_id'], 'items_name_branch_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('items', function (Blueprint $table) {
            // Drop the composite unique constraint
            $table->dropUnique('items_name_branch_unique');
            
            // Restore global unique constraint (this might fail if duplicate names exist)
            $table->unique('name');
        });
    }
};