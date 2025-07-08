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
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->string('reference_no')->unique();
            $table->string('category');
            $table->decimal('amount', 15, 2);
            $table->text('note')->nullable();
            $table->date('expense_date');
            $table->enum('payment_method', ['cash', 'bank_transfer', 'credit_card', 'check', 'other'])->default('cash');
            $table->foreignId('branch_id')->constrained();
            $table->foreignId('user_id')->constrained(); // User who recorded the expense
            $table->string('attachment')->nullable();
            $table->boolean('is_recurring')->default(false);
            $table->enum('recurring_frequency', ['daily', 'weekly', 'monthly', 'quarterly', 'yearly'])->nullable();
            $table->date('next_recurrence_date')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
