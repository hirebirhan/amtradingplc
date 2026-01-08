<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Add soft deletes to categories if not exists
        if (!Schema::hasColumn('categories', 'deleted_at')) {
            Schema::table('categories', function (Blueprint $table) {
                $table->timestamp('deleted_at')->nullable()->index();
                $table->unsignedBigInteger('deleted_by')->nullable();
                $table->foreign('deleted_by')->references('id')->on('users')->nullOnDelete();
            });
        }

        // Add soft deletes to items if not exists
        if (!Schema::hasColumn('items', 'deleted_at')) {
            Schema::table('items', function (Blueprint $table) {
                $table->timestamp('deleted_at')->nullable()->index();
                $table->unsignedBigInteger('deleted_by')->nullable();
                $table->foreign('deleted_by')->references('id')->on('users')->nullOnDelete();
            });
        }
    }

    public function down()
    {
        // Remove soft delete columns
        Schema::table('categories', function (Blueprint $table) {
            if (Schema::hasColumn('categories', 'deleted_by')) {
                $table->dropForeign(['deleted_by']);
                $table->dropColumn('deleted_by');
            }
            if (Schema::hasColumn('categories', 'deleted_at')) {
                $table->dropColumn('deleted_at');
            }
        });

        Schema::table('items', function (Blueprint $table) {
            if (Schema::hasColumn('items', 'deleted_by')) {
                $table->dropForeign(['deleted_by']);
                $table->dropColumn('deleted_by');
            }
            if (Schema::hasColumn('items', 'deleted_at')) {
                $table->dropColumn('deleted_at');
            }
        });
    }
};