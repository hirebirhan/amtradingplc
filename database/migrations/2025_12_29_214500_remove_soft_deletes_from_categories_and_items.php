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
        $this->restoreTable('categories');
        $this->restoreTable('items');
    }

    private function cleanTable(string $tableName)
    {
        if (!Schema::hasTable($tableName)) {
            return;
        }

        $this->ensureBranchIdIndex($tableName);
        $this->dropBranchIsolationIndexes($tableName);
        $this->dropDeletedByForeignKey($tableName);
        $this->dropColumnIfExists($tableName, 'deleted_by');
        $this->dropColumnIfExists($tableName, 'deleted_at');
        $this->addBranchIsolationIndexes($tableName, false);
        $this->dropTempBranchIdIndex($tableName);
    }

    private function restoreTable(string $tableName): void
    {
        if (!Schema::hasTable($tableName)) {
            return;
        }

        $this->ensureBranchIdIndex($tableName);
        $this->dropBranchIsolationIndexes($tableName);

        Schema::table($tableName, function (Blueprint $table) use ($tableName) {
            if (!Schema::hasColumn($tableName, 'deleted_at')) {
                $table->timestamp('deleted_at')->nullable();
            }
            if (!Schema::hasColumn($tableName, 'deleted_by')) {
                $table->unsignedBigInteger('deleted_by')->nullable();
                $table->foreign('deleted_by')->references('id')->on('users')->onDelete('set null');
            }
        });

        $this->addBranchIsolationIndexes($tableName, true);
        $this->dropTempBranchIdIndex($tableName);
    }

    private function dropDeletedByForeignKey(string $tableName): void
    {
        if (!Schema::hasColumn($tableName, 'deleted_by')) {
            return;
        }

        $foreignKeys = Schema::getForeignKeys($tableName);

        if (!$this->hasFkOnColumn($foreignKeys, 'deleted_by')) {
            return;
        }

        Schema::table($tableName, function (Blueprint $table) {
            $table->dropForeign(['deleted_by']);
        });
    }

    private function dropColumnIfExists(string $tableName, string $column): void
    {
        if (!Schema::hasColumn($tableName, $column)) {
            return;
        }

        try {
            Schema::table($tableName, function (Blueprint $table) use ($column) {
                $table->dropColumn($column);
            });
        } catch (\Throwable $e) {
            if (!str_contains($e->getMessage(), "doesn't exist") && !str_contains($e->getMessage(), 'does not exist')) {
                throw $e;
            }
        }
    }

    private function dropIndexIfExists(string $tableName, string $indexName, string $dropMethod): void
    {
        if (!Schema::hasIndex($tableName, $indexName)) {
            return;
        }

        Schema::table($tableName, function (Blueprint $table) use ($indexName, $dropMethod) {
            $table->{$dropMethod}($indexName);
        });
    }

    private function ensureBranchIdIndex(string $tableName): void
    {
        if (!Schema::hasColumn($tableName, 'branch_id')) {
            return;
        }

        $tempIndex = $this->tempBranchIndexName($tableName);

        if (Schema::hasIndex($tableName, $tempIndex)) {
            return;
        }

        Schema::table($tableName, function (Blueprint $table) use ($tempIndex) {
            $table->index(['branch_id'], $tempIndex);
        });
    }

    private function dropTempBranchIdIndex(string $tableName): void
    {
        $this->dropIndexIfExists($tableName, $this->tempBranchIndexName($tableName), 'dropIndex');
    }

    private function tempBranchIndexName(string $tableName): string
    {
        return 'tmp_' . $tableName . '_branch_id_idx';
    }

    private function dropBranchIsolationIndexes(string $tableName): void
    {
        $indexes = match ($tableName) {
            'categories' => [
                ['name' => 'categories_branch_code_unique', 'method' => 'dropUnique'],
                ['name' => 'idx_categories_branch_active', 'method' => 'dropIndex'],
            ],
            'items' => [
                ['name' => 'items_branch_name_unique', 'method' => 'dropUnique'],
                ['name' => 'items_branch_sku_unique', 'method' => 'dropUnique'],
                ['name' => 'idx_items_branch_category', 'method' => 'dropIndex'],
                ['name' => 'idx_items_branch_active', 'method' => 'dropIndex'],
            ],
            default => [],
        };

        foreach ($indexes as $index) {
            $this->dropIndexIfExists($tableName, $index['name'], $index['method']);
        }
    }

    private function addBranchIsolationIndexes(string $tableName, bool $withSoftDeletes): void
    {
        if ($tableName === 'categories') {
            $codeUniqueExists = Schema::hasIndex($tableName, 'categories_branch_code_unique');
            $activeIndexExists = Schema::hasIndex($tableName, 'idx_categories_branch_active');

            if ($codeUniqueExists && $activeIndexExists) {
                return;
            }

            $codeColumns = $withSoftDeletes ? ['branch_id', 'code', 'deleted_at'] : ['branch_id', 'code'];
            $activeColumns = $withSoftDeletes ? ['branch_id', 'is_active', 'deleted_at'] : ['branch_id', 'is_active'];

            Schema::table($tableName, function (Blueprint $table) use ($codeUniqueExists, $activeIndexExists, $codeColumns, $activeColumns) {
                if (!$codeUniqueExists) {
                    $table->unique($codeColumns, 'categories_branch_code_unique');
                }
                if (!$activeIndexExists) {
                    $table->index($activeColumns, 'idx_categories_branch_active');
                }
            });

            return;
        }

        if ($tableName === 'items') {
            $nameUniqueExists = Schema::hasIndex($tableName, 'items_branch_name_unique');
            $skuUniqueExists = Schema::hasIndex($tableName, 'items_branch_sku_unique');
            $categoryIndexExists = Schema::hasIndex($tableName, 'idx_items_branch_category');
            $activeIndexExists = Schema::hasIndex($tableName, 'idx_items_branch_active');

            if ($nameUniqueExists && $skuUniqueExists && $categoryIndexExists && $activeIndexExists) {
                return;
            }

            $nameColumns = $withSoftDeletes ? ['branch_id', 'name', 'deleted_at'] : ['branch_id', 'name'];
            $skuColumns = $withSoftDeletes ? ['branch_id', 'sku', 'deleted_at'] : ['branch_id', 'sku'];
            $categoryColumns = $withSoftDeletes ? ['branch_id', 'category_id', 'deleted_at'] : ['branch_id', 'category_id'];
            $activeColumns = $withSoftDeletes ? ['branch_id', 'is_active', 'deleted_at'] : ['branch_id', 'is_active'];

            Schema::table($tableName, function (Blueprint $table) use ($nameUniqueExists, $skuUniqueExists, $categoryIndexExists, $activeIndexExists, $nameColumns, $skuColumns, $categoryColumns, $activeColumns) {
                if (!$nameUniqueExists) {
                    $table->unique($nameColumns, 'items_branch_name_unique');
                }
                if (!$skuUniqueExists) {
                    $table->unique($skuColumns, 'items_branch_sku_unique');
                }
                if (!$categoryIndexExists) {
                    $table->index($categoryColumns, 'idx_items_branch_category');
                }
                if (!$activeIndexExists) {
                    $table->index($activeColumns, 'idx_items_branch_active');
                }
            });
        }
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
