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
        Schema::table('transfers', function (Blueprint $table) {
            // Add the new polymorphic columns
            $table->string('source_type')->after('reference_no')->default('warehouse');
            $table->unsignedBigInteger('source_id')->after('source_type');
            $table->string('destination_type')->after('source_id')->default('warehouse');  
            $table->unsignedBigInteger('destination_id')->after('destination_type');
            
            // Add note column
            $table->text('note')->nullable()->after('destination_id');
            
            // Update date column name
            $table->timestamp('date_initiated')->nullable()->after('note');
        });
        
        // Migrate existing data to new structure
        DB::statement("UPDATE transfers SET 
            source_type = 'warehouse',
            source_id = from_warehouse_id,
            destination_type = 'warehouse', 
            destination_id = to_warehouse_id,
            date_initiated = COALESCE(transfer_date, created_at)
            WHERE from_warehouse_id IS NOT NULL AND to_warehouse_id IS NOT NULL
        ");
        
        Schema::table('transfers', function (Blueprint $table) {
            // Drop old columns if they exist
            $table->dropForeign(['from_warehouse_id']);
            $table->dropForeign(['to_warehouse_id']);
            
            // Check if these columns exist before dropping them (they might not in all environments)
            if (Schema::hasColumn('transfers', 'from_branch_id')) {
                $table->dropForeign(['from_branch_id']);
                $table->dropColumn('from_branch_id');
            }
            if (Schema::hasColumn('transfers', 'to_branch_id')) {
                $table->dropForeign(['to_branch_id']);
                $table->dropColumn('to_branch_id');
            }
            if (Schema::hasColumn('transfers', 'approved_by')) {
                $table->dropForeign(['approved_by']);
                $table->dropColumn('approved_by');
            }
            if (Schema::hasColumn('transfers', 'transfer_type')) {
                $table->dropColumn('transfer_type');
            }
            if (Schema::hasColumn('transfers', 'approved_at')) {
                $table->dropColumn('approved_at');
            }
            
            $table->dropColumn([
                'from_warehouse_id', 
                'to_warehouse_id', 
                'transfer_date'
            ]);
            
            // Update reference column name
            $table->renameColumn('reference_no', 'reference_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transfers', function (Blueprint $table) {
            // Rename back
            $table->renameColumn('reference_code', 'reference_no');
            
            // Add back old columns
            $table->foreignId('from_warehouse_id')->nullable()->constrained('warehouses');
            $table->foreignId('to_warehouse_id')->nullable()->constrained('warehouses');
            $table->foreignId('from_branch_id')->nullable()->constrained('branches');
            $table->foreignId('to_branch_id')->nullable()->constrained('branches');
            $table->enum('transfer_type', ['warehouse_to_warehouse', 'branch_to_branch', 'warehouse_to_branch', 'branch_to_warehouse'])
                  ->default('warehouse_to_warehouse');
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->timestamp('approved_at')->nullable();
            $table->date('transfer_date');
        });
        
        // Migrate data back
        DB::statement("UPDATE transfers SET 
            from_warehouse_id = CASE WHEN source_type = 'warehouse' THEN source_id ELSE NULL END,
            to_warehouse_id = CASE WHEN destination_type = 'warehouse' THEN destination_id ELSE NULL END,
            from_branch_id = CASE WHEN source_type = 'branch' THEN source_id ELSE NULL END,
            to_branch_id = CASE WHEN destination_type = 'branch' THEN destination_id ELSE NULL END,
            transfer_date = DATE(date_initiated)
        ");
        
        Schema::table('transfers', function (Blueprint $table) {
            // Drop new columns
            $table->dropColumn([
                'source_type',
                'source_id', 
                'destination_type',
                'destination_id',
                'note',
                'date_initiated'
            ]);
        });
    }
}; 