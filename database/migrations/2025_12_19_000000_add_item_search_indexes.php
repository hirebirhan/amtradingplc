<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('items', function (Blueprint $table) {
            // Composite index for fast search
            $table->index(['name', 'sku', 'barcode'], 'items_search_idx');
            
            // Individual indexes for specific searches
            $table->index('sku', 'items_sku_idx');
            $table->index('barcode', 'items_barcode_idx');
            
            // Status filtering
            $table->index(['status', 'name'], 'items_status_name_idx');
        });

        Schema::table('stocks', function (Blueprint $table) {
            // Fast stock lookup
            $table->index(['item_id', 'warehouse_id'], 'stocks_item_warehouse_idx');
        });
    }

    public function down()
    {
        Schema::table('items', function (Blueprint $table) {
            $table->dropIndex('items_search_idx');
            $table->dropIndex('items_sku_idx');
            $table->dropIndex('items_barcode_idx');
            $table->dropIndex('items_status_name_idx');
        });

        Schema::table('stocks', function (Blueprint $table) {
            $table->dropIndex('stocks_item_warehouse_idx');
        });
    }
};