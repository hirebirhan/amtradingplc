<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stocks', function (Blueprint $table) {
            // Add piece and unit tracking columns
            $table->integer('piece_count')->default(0)->after('quantity')->comment('Number of whole pieces in stock');
            $table->decimal('total_units', 15, 2)->default(0)->after('piece_count')->comment('Total units available across all pieces');
            $table->decimal('current_piece_units', 15, 2)->nullable()->after('total_units')->comment('Units remaining in the current piece (for partial unit sales)');
            
            // Keep existing quantity for backward compatibility (will be calculated)
            $table->decimal('quantity', 15, 2)->default(0)->change();
        });
    }

    public function down(): void
    {
        Schema::table('stocks', function (Blueprint $table) {
            $table->dropColumn(['piece_count', 'total_units', 'current_piece_units']);
        });
    }
};