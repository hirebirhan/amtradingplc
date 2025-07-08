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
        // This migration is disabled - see 2024_01_01_000001_fix_stock_reservations_foreign_key.php
        // Schema::create('stock_reservations', function (Blueprint $table) {
        //     $table->id();
        //     $table->foreignId('item_id')->constrained('items')->cascadeOnDelete();
        //     $table->string('location_type'); // 'warehouse' or 'branch'
        //     $table->unsignedBigInteger('location_id'); // warehouse_id or branch_id
        //     $table->decimal('quantity', 15, 2);
        //     $table->string('reference_type'); // 'transfer', 'sale', etc.
        //     $table->unsignedBigInteger('reference_id'); // transfer_id, sale_id, etc.
        //     $table->timestamp('expires_at');
        //     $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
        //     $table->timestamps();

        //     // Indexes for performance
        //     $table->index(['item_id', 'location_type', 'location_id']);
        //     $table->index(['reference_type', 'reference_id']);
        //     $table->index('expires_at');
            
        //     // Composite index for quick lookups
        //     $table->index(['item_id', 'location_type', 'location_id', 'expires_at']);
        // });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_reservations');
    }
}; 