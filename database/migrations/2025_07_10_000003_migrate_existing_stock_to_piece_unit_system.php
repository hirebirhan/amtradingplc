<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Migrate existing stock data to piece/unit system
        DB::statement('
            UPDATE stocks s
            JOIN items i ON s.item_id = i.id
            SET 
                s.piece_count = s.quantity,
                s.total_units = s.quantity * COALESCE(i.unit_quantity, 1)
            WHERE s.piece_count = 0 AND s.total_units = 0
        ');
    }

    public function down(): void
    {
        // Revert to quantity-only system
        DB::statement('
            UPDATE stocks 
            SET piece_count = 0, total_units = 0
        ');
    }
};