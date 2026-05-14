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
        if (config('database.default') === 'mysql') {
            DB::statement("ALTER TABLE sales MODIFY COLUMN payment_method ENUM('cash', 'bank_transfer', 'telebirr', 'credit_advance', 'full_credit') DEFAULT 'cash'");
            DB::statement("ALTER TABLE sale_payments MODIFY COLUMN payment_method ENUM('cash', 'bank_transfer', 'telebirr', 'credit_advance', 'full_credit') DEFAULT 'cash'");
        }
    }

    public function down(): void
    {
        if (config('database.default') === 'mysql') {
            DB::statement("ALTER TABLE sales MODIFY COLUMN payment_method ENUM('cash', 'bank_transfer', 'telebirr', 'credit_advance', 'credit_full') DEFAULT 'cash'");
            DB::statement("ALTER TABLE sale_payments MODIFY COLUMN payment_method ENUM('cash', 'bank_transfer', 'telebirr', 'credit_advance', 'credit_full') DEFAULT 'cash'");
        }
    }
};