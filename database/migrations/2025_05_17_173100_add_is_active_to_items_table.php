<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('items', function (Blueprint $table) {
            // Add is_active column with default value true
            $table->boolean('is_active')->default(true)->after('status');
        });
        
        // Now update is_active based on status column in a separate step
        DB::statement('UPDATE items SET is_active = (status = "active")');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->dropColumn('is_active');
        });
    }
}; 