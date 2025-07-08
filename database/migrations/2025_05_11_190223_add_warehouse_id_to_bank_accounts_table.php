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
        Schema::table('bank_accounts', function (Blueprint $table) {
            // Make branch_id nullable
            $table->foreignId('branch_id')->nullable()->change();
            
            // Add warehouse_id
            $table->foreignId('warehouse_id')->nullable()->after('branch_id')->constrained();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bank_accounts', function (Blueprint $table) {
            $table->dropConstrainedForeignId('warehouse_id');
            
            // Restore branch_id to be required if it was required before
            // Note: This assumes branch_id was not nullable originally
            // $table->foreignId('branch_id')->nullable(false)->change();
        });
    }
};
