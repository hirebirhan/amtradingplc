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
        Schema::table('stocks', function (Blueprint $table) {
            $table->unsignedBigInteger('branch_id')->nullable()->after('warehouse_id');
            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('set null');
            
            // Add index for better performance
            $table->index(['branch_id', 'item_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stocks', function (Blueprint $table) {
            $table->dropForeign(['branch_id']);
            $table->dropIndex(['branch_id', 'item_id']);
            $table->dropColumn('branch_id');
        });
    }
};
