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
        // Tables that need audit fields
        $tables = [
            'items',
            'categories', 
            'sales',
            'purchases',
            'transfers',
            'credits',
            'customers',
            'suppliers',
            'stocks',
            'branches',
            'warehouses',
            'expenses'
        ];

        foreach ($tables as $tableName) {
            if (Schema::hasTable($tableName)) {
                Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                    // Add audit fields if they don't exist
                    if (!Schema::hasColumn($tableName, 'created_by')) {
                        $table->unsignedBigInteger('created_by')->nullable()->after('id');
                        $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
                    }
                    if (!Schema::hasColumn($tableName, 'updated_by')) {
                        $table->unsignedBigInteger('updated_by')->nullable()->after('created_by');
                        $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
                    }
                    if (!Schema::hasColumn($tableName, 'deleted_by')) {
                        $table->unsignedBigInteger('deleted_by')->nullable()->after('updated_by');
                        $table->foreign('deleted_by')->references('id')->on('users')->onDelete('set null');
                    }
                });
            }
        }

        // Add indexes for better performance
        foreach ($tables as $tableName) {
            if (Schema::hasTable($tableName)) {
                Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                    if (Schema::hasColumn($tableName, 'created_by')) {
                        $table->index('created_by');
                    }
                    if (Schema::hasColumn($tableName, 'updated_by')) {
                        $table->index('updated_by');
                    }
                    if (Schema::hasColumn($tableName, 'created_at') && Schema::hasColumn($tableName, 'created_by')) {
                        $table->index(['created_at', 'created_by']);
                    }
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tables = [
            'items',
            'categories', 
            'sales',
            'purchases',
            'transfers',
            'credits',
            'customers',
            'suppliers',
            'stocks',
            'branches',
            'warehouses',
            'expenses'
        ];

        foreach ($tables as $tableName) {
            if (Schema::hasTable($tableName)) {
                Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                    // Drop foreign keys first (if they exist)
                    if (Schema::hasColumn($tableName, 'created_by')) {
                        $table->dropForeign(['created_by']);
                    }
                    if (Schema::hasColumn($tableName, 'updated_by')) {
                        $table->dropForeign(['updated_by']);
                    }
                    if (Schema::hasColumn($tableName, 'deleted_by')) {
                        $table->dropForeign(['deleted_by']);
                    }
                    
                    // Drop indexes (if they exist)
                    if (Schema::hasColumn($tableName, 'created_by')) {
                        $table->dropIndex(['created_by']);
                    }
                    if (Schema::hasColumn($tableName, 'updated_by')) {
                        $table->dropIndex(['updated_by']);
                    }
                    if (Schema::hasColumn($tableName, 'created_at') && Schema::hasColumn($tableName, 'created_by')) {
                        $table->dropIndex(['created_at', 'created_by']);
                    }
                    
                    // Drop columns
                    $table->dropColumn(['created_by', 'updated_by', 'deleted_by']);
                });
            }
        }
    }
};
