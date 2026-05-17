<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Sales filtered by branch + date range (dashboard, reports)
        Schema::table('sales', function (Blueprint $table) {
            $table->index(['branch_id', 'sale_date'], 'sales_branch_date_idx');
        });

        // Purchases filtered by branch + date range
        Schema::table('purchases', function (Blueprint $table) {
            $table->index(['branch_id', 'purchase_date'], 'purchases_branch_date_idx');
        });

        // Price history per item sorted by date (price-history API, item detail page)
        Schema::table('price_history', function (Blueprint $table) {
            $table->index(['item_id', 'created_at'], 'price_history_item_date_idx');
        });

        // Credits: morph lookup (credit->reference) and status filtering
        Schema::table('credits', function (Blueprint $table) {
            $table->index(['reference_type', 'reference_id'], 'credits_reference_idx');
            $table->index(['status', 'credit_type'], 'credits_status_type_idx');
        });

        // Stock history per item over time (stock card queries)
        Schema::table('stock_histories', function (Blueprint $table) {
            $table->index(['item_id', 'created_at'], 'stock_histories_item_date_idx');
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropIndex('sales_branch_date_idx');
        });

        Schema::table('purchases', function (Blueprint $table) {
            $table->dropIndex('purchases_branch_date_idx');
        });

        Schema::table('price_history', function (Blueprint $table) {
            $table->dropIndex('price_history_item_date_idx');
        });

        Schema::table('credits', function (Blueprint $table) {
            $table->dropIndex('credits_reference_idx');
            $table->dropIndex('credits_status_type_idx');
        });

        Schema::table('stock_histories', function (Blueprint $table) {
            $table->dropIndex('stock_histories_item_date_idx');
        });
    }
};
