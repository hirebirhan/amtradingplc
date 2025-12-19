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
        // Add receiver account details to purchases table
        Schema::table('purchases', function (Blueprint $table) {
            if (!Schema::hasColumn('purchases', 'receiver_bank_name')) {
                $table->string('receiver_bank_name')->nullable()->after('transaction_number');
            }
            if (!Schema::hasColumn('purchases', 'receiver_account_holder')) {
                $table->string('receiver_account_holder')->nullable()->after('receiver_bank_name');
            }
            if (!Schema::hasColumn('purchases', 'receiver_account_number')) {
                $table->string('receiver_account_number')->nullable()->after('receiver_account_holder');
            }
        });

        // Add receiver account details to sales table
        Schema::table('sales', function (Blueprint $table) {
            if (!Schema::hasColumn('sales', 'receiver_bank_name')) {
                $table->string('receiver_bank_name')->nullable()->after('transaction_number');
            }
            if (!Schema::hasColumn('sales', 'receiver_account_holder')) {
                $table->string('receiver_account_holder')->nullable()->after('receiver_bank_name');
            }
            if (!Schema::hasColumn('sales', 'receiver_account_number')) {
                $table->string('receiver_account_number')->nullable()->after('receiver_account_holder');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            if (Schema::hasColumn('purchases', 'receiver_account_number')) {
                $table->dropColumn('receiver_account_number');
            }
            if (Schema::hasColumn('purchases', 'receiver_account_holder')) {
                $table->dropColumn('receiver_account_holder');
            }
            if (Schema::hasColumn('purchases', 'receiver_bank_name')) {
                $table->dropColumn('receiver_bank_name');
            }
        });

        Schema::table('sales', function (Blueprint $table) {
            if (Schema::hasColumn('sales', 'receiver_account_number')) {
                $table->dropColumn('receiver_account_number');
            }
            if (Schema::hasColumn('sales', 'receiver_account_holder')) {
                $table->dropColumn('receiver_account_holder');
            }
            if (Schema::hasColumn('sales', 'receiver_bank_name')) {
                $table->dropColumn('receiver_bank_name');
            }
        });
    }
};

