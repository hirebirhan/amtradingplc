<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stocks', function (Blueprint $table) {
            // Add current piece units tracking for partial unit sales
            $table->decimal('current_piece_units', 15, 2)->nullable()->after('total_units')
                ->comment('Units remaining in the current piece (for partial unit sales)');
        });
    }

    public function down(): void
    {
        Schema::table('stocks', function (Blueprint $table) {
            $table->dropColumn('current_piece_units');
        });
    }
};