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
            DB::statement("ALTER TABLE purchases MODIFY COLUMN payment_method ENUM('cash', 'bank_transfer', 'credit_advance', 'telebirr', 'full_credit') DEFAULT 'cash'");
        }
    }

    public function down(): void
    {
        if (config('database.default') === 'mysql') {
            DB::statement("ALTER TABLE purchases MODIFY COLUMN payment_method ENUM('cash', 'bank_transfer', 'credit_advance', 'telebirr') DEFAULT 'cash'");
        }
    }
};
