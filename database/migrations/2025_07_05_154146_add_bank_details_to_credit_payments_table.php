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
        Schema::table('credit_payments', function (Blueprint $table) {
            $table->string('receiver_bank_name')->nullable()->after('reference_no');
            $table->string('receiver_account_holder')->nullable()->after('receiver_bank_name');
            $table->string('receiver_account_number')->nullable()->after('receiver_account_holder');
            $table->string('reference')->nullable()->after('receiver_account_number'); // Rename notes to reference
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('credit_payments', function (Blueprint $table) {
            $table->dropColumn([
                'receiver_bank_name',
                'receiver_account_holder', 
                'receiver_account_number',
                'reference'
            ]);
        });
    }
};
