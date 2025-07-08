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
        // Drop the stock_reservations table if it exists (to recreate it properly)
        Schema::dropIfExists('stock_reservations');
        
        // Recreate the stock_reservations table with proper constraints
        Schema::create('stock_reservations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('item_id');
            $table->string('location_type'); // 'warehouse' or 'branch'
            $table->unsignedBigInteger('location_id'); // warehouse_id or branch_id
            $table->decimal('quantity', 15, 2);
            $table->string('reference_type'); // 'transfer', 'sale', etc.
            $table->unsignedBigInteger('reference_id'); // transfer_id, sale_id, etc.
            $table->timestamp('expires_at');
            $table->unsignedBigInteger('created_by');
            $table->timestamps();

            // Add foreign key constraints after table creation
            $table->foreign('item_id')->references('id')->on('items')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');

            // Indexes for performance (with shorter names)
            $table->index(['item_id', 'location_type', 'location_id'], 'stock_res_item_loc_idx');
            $table->index(['reference_type', 'reference_id'], 'stock_res_ref_idx');
            $table->index(['expires_at'], 'stock_res_expires_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_reservations');
    }
}; 