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
        // Make purchase_date default to current date using proper MySQL syntax
        DB::statement('ALTER TABLE purchases MODIFY purchase_date DATE NOT NULL DEFAULT (CURRENT_DATE())');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove the default value
        DB::statement('ALTER TABLE purchases MODIFY purchase_date DATE NOT NULL');
    }
};
