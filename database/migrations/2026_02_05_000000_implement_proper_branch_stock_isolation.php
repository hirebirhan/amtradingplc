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
        // Update stocks with proper branch_id
        DB::statement("
            UPDATE stocks 
            SET branch_id = (
                SELECT bw.branch_id 
                FROM branch_warehouse bw 
                WHERE bw.warehouse_id = stocks.warehouse_id 
                LIMIT 1
            )
            WHERE branch_id IS NULL
        ");

        // Add unique constraint
        Schema::table('stocks', function (Blueprint $table) {
            $table->unique(['warehouse_id', 'item_id'], 'stocks_warehouse_item_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stocks', function (Blueprint $table) {
            $table->dropUnique('stocks_warehouse_item_unique');
        });
    }
};