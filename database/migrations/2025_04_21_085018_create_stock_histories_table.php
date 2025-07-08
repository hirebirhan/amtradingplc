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
        Schema::create('stock_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_id')->constrained('warehouses');
            $table->foreignId('item_id')->constrained('items');
            $table->decimal('quantity_before', 15, 2)->default(0);
            $table->decimal('quantity_after', 15, 2)->default(0);
            $table->decimal('quantity_change', 15, 2);
            $table->string('reference_type'); // Purchase, Sale, Return, Transfer, Adjustment, etc.
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->string('description')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            // Index for polymorphic relationship lookup
            $table->index(['reference_type', 'reference_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_histories');
    }
};
