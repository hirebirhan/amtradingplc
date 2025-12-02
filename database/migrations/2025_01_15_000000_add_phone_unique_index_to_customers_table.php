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
        Schema::table('customers', function (Blueprint $table) {
            // Add unique index for phone numbers (excluding soft deleted records)
            $table->index(['phone', 'deleted_at'], 'customers_phone_deleted_at_index');
            
            // Add index for email (excluding soft deleted records) 
            $table->index(['email', 'deleted_at'], 'customers_email_deleted_at_index');
            
            // Add index for name searches
            $table->index('name', 'customers_name_index');
            
            // Add index for customer type filtering
            $table->index('customer_type', 'customers_type_index');
            
            // Add index for active customers
            $table->index('is_active', 'customers_active_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropIndex('customers_phone_deleted_at_index');
            $table->dropIndex('customers_email_deleted_at_index');
            $table->dropIndex('customers_name_index');
            $table->dropIndex('customers_type_index');
            $table->dropIndex('customers_active_index');
        });
    }
};