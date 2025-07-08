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
        Schema::create('credits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('supplier_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('amount', 15, 2);
            $table->decimal('paid_amount', 15, 2)->default(0);
            $table->decimal('balance', 15, 2);
            $table->string('reference_no')->nullable();
            $table->string('reference_type')->nullable(); // sale, purchase, manual
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->enum('credit_type', ['receivable', 'payable']);
            $table->text('description')->nullable();
            $table->date('credit_date');
            $table->date('due_date');
            $table->enum('status', ['active', 'partially_paid', 'paid', 'overdue', 'cancelled'])->default('active');
            $table->foreignId('user_id')->constrained(); // User who created the credit
            $table->foreignId('branch_id')->constrained();
            $table->foreignId('warehouse_id')->nullable()->constrained();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('credits');
    }
};
