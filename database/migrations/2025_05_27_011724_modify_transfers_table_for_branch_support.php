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
        Schema::table('transfers', function (Blueprint $table) {
            // Make warehouse fields nullable to support branch transfers
            $table->foreignId('from_warehouse_id')->nullable()->change();
            $table->foreignId('to_warehouse_id')->nullable()->change();
            
            // Add branch fields for branch-to-branch transfers
            $table->foreignId('from_branch_id')->nullable()->after('to_warehouse_id')->constrained('branches');
            $table->foreignId('to_branch_id')->nullable()->after('from_branch_id')->constrained('branches');
            
            // Add transfer type to distinguish between different transfer types
            $table->enum('transfer_type', ['warehouse_to_warehouse', 'branch_to_branch', 'warehouse_to_branch', 'branch_to_warehouse'])
                  ->after('reference_no')
                  ->default('warehouse_to_warehouse');
                  
            // Add approved_by and approved_at fields for approval workflow
            $table->foreignId('approved_by')->nullable()->after('user_id')->constrained('users');
            $table->timestamp('approved_at')->nullable()->after('approved_by');
            
            // Update status enum to include more statuses
            $table->enum('status', ['pending', 'approved', 'in_transit', 'completed', 'cancelled', 'rejected'])
                  ->default('pending')
                  ->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transfers', function (Blueprint $table) {
            // Remove new columns
            $table->dropConstrainedForeignId('from_branch_id');
            $table->dropConstrainedForeignId('to_branch_id');
            $table->dropConstrainedForeignId('approved_by');
            $table->dropColumn(['transfer_type', 'approved_at']);
            
            // Restore original warehouse constraints (make them required again)
            $table->foreignId('from_warehouse_id')->nullable(false)->change();
            $table->foreignId('to_warehouse_id')->nullable(false)->change();
            
            // Restore original status enum
            $table->enum('status', ['pending', 'completed', 'cancelled'])->default('pending')->change();
        });
    }
};
