<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            // Drop existing slug unique constraint
            $table->dropUnique(['slug']);
            
            // Add branch-specific slug constraint
            $table->unique(['branch_id', 'slug'], 'categories_branch_slug_unique');
        });
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropUnique('categories_branch_slug_unique');
            $table->unique('slug');
        });
    }
};