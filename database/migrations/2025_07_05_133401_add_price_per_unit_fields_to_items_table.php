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
            $table->decimal('cost_price_per_unit', 10, 2)->nullable()->after('selling_price');
            $table->decimal('selling_price_per_unit', 10, 2)->nullable()->after('cost_price_per_unit');
            $table->string('item_unit', 50)->nullable()->after('unit_quantity')->comment('Unit of items within a piece (kg, liter, etc.)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->dropColumn(['cost_price_per_unit', 'selling_price_per_unit', 'item_unit']);
        });
    }
};
