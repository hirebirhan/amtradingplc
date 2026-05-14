<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (config('database.default') === 'mysql') {
            DB::statement('
                UPDATE stocks s
                JOIN items i ON s.item_id = i.id
                SET
                    s.piece_count = s.quantity,
                    s.total_units = s.quantity * COALESCE(i.unit_quantity, 1)
                WHERE s.piece_count = 0 AND s.total_units = 0
            ');
        } else {
            DB::statement('
                UPDATE stocks
                SET
                    piece_count = quantity,
                    total_units = quantity * COALESCE(
                        (SELECT unit_quantity FROM items WHERE items.id = stocks.item_id),
                        1
                    )
                WHERE piece_count = 0 AND total_units = 0
            ');
        }
    }

    public function down(): void
    {
        DB::statement('UPDATE stocks SET piece_count = 0, total_units = 0');
    }
};