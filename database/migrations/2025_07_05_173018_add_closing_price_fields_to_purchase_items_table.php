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
        Schema::table('purchase_items', function (Blueprint $table) {
            $table->decimal('closing_unit_price', 15, 2)->nullable()->after('unit_cost');
            $table->decimal('total_closing_cost', 15, 2)->nullable()->after('closing_unit_price');
            $table->decimal('profit_loss_per_item', 15, 2)->nullable()->after('total_closing_cost');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_items', function (Blueprint $table) {
            $table->dropColumn(['closing_unit_price', 'total_closing_cost', 'profit_loss_per_item']);
        });
    }
};
