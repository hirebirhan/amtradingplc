<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use App\Models\Category;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // First add the column without unique constraint
        Schema::table('categories', function (Blueprint $table) {
            $table->string('slug')->after('name')->nullable();
        });

        // Generate slugs for existing categories
        Category::whereNull('slug')->each(function ($category) {
            $category->slug = Str::slug($category->name);
            $category->saveQuietly();
        });

        // Now add unique constraint
        Schema::table('categories', function (Blueprint $table) {
            $table->unique('slug');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropUnique(['slug']);
            $table->dropColumn('slug');
        });
    }
}; 