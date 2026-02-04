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
        // Add a temporary column with the new enum values
        Schema::table('purchases', function (Blueprint $table) {
            $table->enum('status_new', ['draft', 'confirmed', 'received', 'cancelled'])->default('confirmed');
        });

        // Map old values to new values
        DB::statement("UPDATE purchases SET status_new = CASE 
            WHEN status = 'pending' THEN 'confirmed'
            WHEN status = 'ordered' THEN 'confirmed'
            WHEN status = 'partial' THEN 'confirmed'
            WHEN status = 'received' THEN 'received'
            WHEN status = 'canceled' THEN 'cancelled'
            ELSE 'confirmed'
        END");

        // Drop the old column and rename the new one
        Schema::table('purchases', function (Blueprint $table) {
            $table->dropColumn('status');
        });

        Schema::table('purchases', function (Blueprint $table) {
            $table->renameColumn('status_new', 'status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Add temporary column with old enum values
        Schema::table('purchases', function (Blueprint $table) {
            $table->enum('status_old', ['pending', 'received', 'partial', 'ordered', 'canceled'])->default('pending');
        });

        // Map new values back to old values
        DB::statement("UPDATE purchases SET status_old = CASE 
            WHEN status = 'draft' THEN 'pending'
            WHEN status = 'confirmed' THEN 'pending'
            WHEN status = 'received' THEN 'received'
            WHEN status = 'cancelled' THEN 'canceled'
            ELSE 'pending'
        END");

        // Drop the new column and rename the old one
        Schema::table('purchases', function (Blueprint $table) {
            $table->dropColumn('status');
        });

        Schema::table('purchases', function (Blueprint $table) {
            $table->renameColumn('status_old', 'status');
        });
    }
};