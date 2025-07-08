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
        // Update sales table to add payment details fields
        Schema::table('sales', function (Blueprint $table) {
            // Check if the column exists first
            if (Schema::hasColumn('sales', 'payment_method')) {
                $table->enum('payment_method', ['cash', 'bank_transfer', 'telebirr', 'credit_advance', 'credit_full'])
                      ->default('cash')
                      ->change();
            } else {
                $table->enum('payment_method', ['cash', 'bank_transfer', 'telebirr', 'credit_advance', 'credit_full'])
                      ->default('cash')
                      ->after('payment_status');
            }

            // Add new payment details fields
            if (!Schema::hasColumn('sales', 'transaction_number')) {
                $table->string('transaction_number')->nullable()->after('payment_method');
            }

            if (!Schema::hasColumn('sales', 'bank_name')) {
                $table->string('bank_name')->nullable()->after('transaction_number');
            }

            if (!Schema::hasColumn('sales', 'account_number')) {
                $table->string('account_number')->nullable()->after('bank_name');
            }

            if (!Schema::hasColumn('sales', 'receipt_url')) {
                $table->string('receipt_url')->nullable()->after('account_number');
            }

            if (!Schema::hasColumn('sales', 'receipt_image')) {
                $table->string('receipt_image')->nullable()->after('receipt_url');
            }

            if (!Schema::hasColumn('sales', 'advance_amount')) {
                $table->decimal('advance_amount', 15, 2)->default(0)->after('receipt_image');
            }
        });

        // Update sale payments table to include payment details
        Schema::table('sale_payments', function (Blueprint $table) {
            // Check if the column exists first
            if (Schema::hasColumn('sale_payments', 'payment_method')) {
                $table->enum('payment_method', ['cash', 'bank_transfer', 'telebirr', 'credit_advance', 'credit_full'])
                      ->default('cash')
                      ->change();
            } else {
                $table->enum('payment_method', ['cash', 'bank_transfer', 'telebirr', 'credit_advance', 'credit_full'])
                      ->default('cash');
            }

            // Add new payment details fields
            if (!Schema::hasColumn('sale_payments', 'transaction_number')) {
                $table->string('transaction_number')->nullable()->after('payment_method');
            }

            if (!Schema::hasColumn('sale_payments', 'bank_name')) {
                $table->string('bank_name')->nullable()->after('transaction_number');
            }

            if (!Schema::hasColumn('sale_payments', 'account_number')) {
                $table->string('account_number')->nullable()->after('bank_name');
            }

            if (!Schema::hasColumn('sale_payments', 'receipt_url')) {
                $table->string('receipt_url')->nullable()->after('account_number');
            }

            if (!Schema::hasColumn('sale_payments', 'receipt_image')) {
                $table->string('receipt_image')->nullable()->after('receipt_url');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert sales table changes
        Schema::table('sales', function (Blueprint $table) {
            if (Schema::hasColumn('sales', 'payment_method')) {
                $table->enum('payment_method', ['cash', 'credit_card', 'bank_transfer', 'check'])
                      ->default('cash')
                      ->change();
            }

            $table->dropColumn([
                'transaction_number',
                'bank_name',
                'account_number',
                'receipt_url',
                'receipt_image',
                'advance_amount'
            ]);
        });

        // Revert sale payments table changes
        Schema::table('sale_payments', function (Blueprint $table) {
            if (Schema::hasColumn('sale_payments', 'payment_method')) {
                $table->enum('payment_method', ['cash', 'credit_card', 'bank_transfer', 'check'])
                      ->default('cash')
                      ->change();
            }

            $table->dropColumn([
                'transaction_number',
                'bank_name',
                'account_number',
                'receipt_url',
                'receipt_image'
            ]);
        });
    }
};