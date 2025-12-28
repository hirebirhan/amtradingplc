<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Alter the enum to include telebirr
        DB::statement("ALTER TABLE credit_payments MODIFY COLUMN payment_method ENUM('cash', 'bank_transfer', 'telebirr', 'credit_card', 'check', 'other') DEFAULT 'cash'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert back to original enum
        DB::statement("ALTER TABLE credit_payments MODIFY COLUMN payment_method ENUM('cash', 'bank_transfer', 'credit_card', 'check', 'other') DEFAULT 'cash'");
    }
};