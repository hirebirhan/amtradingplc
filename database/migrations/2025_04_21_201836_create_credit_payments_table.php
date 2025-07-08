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
        Schema::create('credit_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('credit_id')->constrained()->onDelete('cascade');
            $table->decimal('amount', 15, 2);
            $table->enum('payment_method', ['cash', 'bank_transfer', 'credit_card', 'check', 'other'])->default('cash');
            $table->string('reference_no')->nullable();
            $table->date('payment_date');
            $table->text('notes')->nullable();
            $table->foreignId('user_id')->constrained(); // User who recorded the payment
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('credit_payments');
    }
};
