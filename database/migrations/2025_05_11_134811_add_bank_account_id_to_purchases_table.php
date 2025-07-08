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
        Schema::table('purchases', function (Blueprint $table) {
            // Check if the column doesn't already exist
            if (!Schema::hasColumn('purchases', 'bank_account_id')) {
                $table->foreignId('bank_account_id')->nullable()->after('transaction_number')
                      ->references('id')->on('bank_accounts')->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            // Check if the column exists before dropping
            if (Schema::hasColumn('purchases', 'bank_account_id')) {
                $table->dropForeign(['bank_account_id']);
                $table->dropColumn('bank_account_id');
            }
        });
    }
};
