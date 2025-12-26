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
        });

        // Add receiver account details to sales table
        Schema::table('sales', function (Blueprint $table) {
            if (!Schema::hasColumn('sales', 'receiver_bank_name')) {
                $table->string('receiver_bank_name')->nullable()->after('transaction_number');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            if (Schema::hasColumn('purchases', 'receiver_bank_name')) {
                $table->dropColumn('receiver_bank_name');
            }
        });

        Schema::table('sales', function (Blueprint $table) {
            if (Schema::hasColumn('sales', 'receiver_bank_name')) {
                $table->dropColumn('receiver_bank_name');
            }
        });
    }
};