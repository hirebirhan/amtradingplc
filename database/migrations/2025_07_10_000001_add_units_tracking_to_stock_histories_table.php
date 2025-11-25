<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock_histories', function (Blueprint $table) {
            // Add unit tracking to history
            $table->decimal('units_before', 15, 2)->nullable()->after('quantity_after')->comment('Units before transaction');
            $table->decimal('units_after', 15, 2)->nullable()->after('units_before')->comment('Units after transaction');
            $table->decimal('units_change', 15, 2)->nullable()->after('units_after')->comment('Units change amount');
        });
    }

    public function down(): void
    {
        Schema::table('stock_histories', function (Blueprint $table) {
            $table->dropColumn(['units_before', 'units_after', 'units_change']);
        });
    }
};