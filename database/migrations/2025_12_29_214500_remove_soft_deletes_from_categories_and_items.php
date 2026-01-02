<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        $this->cleanTable('categories');
        $this->cleanTable('items');
    }

    public function down()
    {
        Schema::table('categories', function (Blueprint $table) {
            if (!Schema::hasColumn('categories', 'deleted_at')) {
                $table->timestamp('deleted_at')->nullable();
            }
            if (!Schema::hasColumn('categories', 'deleted_by')) {
                $table->unsignedBigInteger('deleted_by')->nullable();
                $table->foreign('deleted_by')->references('id')->on('users')->onDelete('set null');
            }
        });

        Schema::table('items', function (Blueprint $table) {
            if (!Schema::hasColumn('items', 'deleted_at')) {
                $table->timestamp('deleted_at')->nullable();
            }
            if (!Schema::hasColumn('items', 'deleted_by')) {
                $table->unsignedBigInteger('deleted_by')->nullable();
                $table->foreign('deleted_by')->references('id')->on('users')->onDelete('set null');
            }
        });
    }

    private function cleanTable(string $tableName)
    {
        // 1. Drop foreign keys if they exist
        Schema::table($tableName, function (Blueprint $table) use ($tableName) {
            $foreignKeys = Schema::getForeignKeys($tableName);
            $fkNames = array_map(fn($fk) => $fk['name'], $foreignKeys);
            
            // Check for foreign key on deleted_by
            // Laravel usually names it {table}_{column}_foreign
            $expectedFk = $tableName . '_deleted_by_foreign';
            
            if (in_array($expectedFk, $fkNames) || $this->hasFkOnColumn($foreignKeys, 'deleted_by')) {
                $table->dropForeign(['deleted_by']);
            }
        });

        // 2. Drop columns if they exist
        Schema::table($tableName, function (Blueprint $table) use ($tableName) {
            if (Schema::hasColumn($tableName, 'deleted_by')) {
                $table->dropColumn('deleted_by');
            }
            if (Schema::hasColumn($tableName, 'deleted_at')) {
                $table->dropColumn('deleted_at');
            }
        });
    }

    private function hasFkOnColumn(array $foreignKeys, string $columnName): bool
    {
        foreach ($foreignKeys as $fk) {
            if (in_array($columnName, $fk['columns'])) {
                return true;
            }
        }
        return false;
    }
};