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
        // Modify the payment_method enum to include 'full_credit'
        DB::statement("ALTER TABLE purchases MODIFY COLUMN payment_method ENUM('cash', 'bank_transfer', 'credit_advance', 'telebirr', 'full_credit') DEFAULT 'cash'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert back to the original enum values
        DB::statement("ALTER TABLE purchases MODIFY COLUMN payment_method ENUM('cash', 'bank_transfer', 'credit_advance', 'telebirr') DEFAULT 'cash'");
    }
};
