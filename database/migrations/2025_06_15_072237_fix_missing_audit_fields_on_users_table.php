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
        Schema::table('users', function (Blueprint $table) {
            // Add audit fields if they don't exist
            if (!Schema::hasColumn('users', 'created_by')) {
                $table->unsignedBigInteger('created_by')->nullable()->after('id');
                $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            }
            if (!Schema::hasColumn('users', 'updated_by')) {
                $table->unsignedBigInteger('updated_by')->nullable()->after('created_by');
                $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
            }
            if (!Schema::hasColumn('users', 'deleted_by')) {
                $table->unsignedBigInteger('deleted_by')->nullable()->after('updated_by');
                $table->foreign('deleted_by')->references('id')->on('users')->onDelete('set null');
            }
        });

        // Add indexes for performance
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'created_by') && !$this->hasIndex('users', 'users_created_by_index')) {
                $table->index('created_by');
            }
            if (Schema::hasColumn('users', 'updated_by') && !$this->hasIndex('users', 'users_updated_by_index')) {
                $table->index('updated_by');
            }
            if (Schema::hasColumn('users', 'created_at') && Schema::hasColumn('users', 'created_by') && !$this->hasIndex('users', 'users_created_at_created_by_index')) {
                $table->index(['created_at', 'created_by']);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Drop foreign keys and indexes first
            if (Schema::hasColumn('users', 'created_by')) {
                $table->dropForeign(['created_by']);
                if ($this->hasIndex('users', 'users_created_by_index')) {
                    $table->dropIndex(['created_by']);
                }
            }
            if (Schema::hasColumn('users', 'updated_by')) {
                $table->dropForeign(['updated_by']);
                if ($this->hasIndex('users', 'users_updated_by_index')) {
                    $table->dropIndex(['updated_by']);
                }
            }
            if (Schema::hasColumn('users', 'deleted_by')) {
                $table->dropForeign(['deleted_by']);
            }
            if ($this->hasIndex('users', 'users_created_at_created_by_index')) {
                $table->dropIndex(['created_at', 'created_by']);
            }
            
            // Drop columns
            $table->dropColumn(['created_by', 'updated_by', 'deleted_by']);
        });
    }

    /**
     * Check if index exists on table
     */
    private function hasIndex(string $table, string $indexName): bool
    {
        $indexes = \Illuminate\Support\Facades\DB::select("SHOW INDEX FROM {$table}");
        foreach ($indexes as $index) {
            if ($index->Key_name === $indexName) {
                return true;
            }
        }
        return false;
    }
};
