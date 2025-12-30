<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Remove foreign keys and soft delete columns from categories table
        Schema::table('categories', function (Blueprint $table) {
            if (Schema::hasColumn('categories', 'deleted_by')) {
                try {
                    $table->dropForeign(['deleted_by']);
                } catch (\Exception $e) {
                    // Ignore if foreign key doesn't exist or is already dropped
                }
                $table->dropColumn('deleted_by');
            }
            if (Schema::hasColumn('categories', 'deleted_at')) {
                $table->dropColumn('deleted_at');
            }
        });

        // Remove foreign keys and soft delete columns from items table  
        Schema::table('items', function (Blueprint $table) {
            if (Schema::hasColumn('items', 'deleted_by')) {
                try {
                    $table->dropForeign(['deleted_by']);
                } catch (\Exception $e) {
                    // Ignore
                }
                $table->dropColumn('deleted_by');
            }
            if (Schema::hasColumn('items', 'deleted_at')) {
                $table->dropColumn('deleted_at');
            }
        });
    }

    public function down()
    {
        // Add back soft delete columns and foreign keys
        Schema::table('categories', function (Blueprint $table) {
            $table->timestamp('deleted_at')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->foreign('deleted_by')->references('id')->on('users');
        });

        Schema::table('items', function (Blueprint $table) {
            $table->timestamp('deleted_at')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->foreign('deleted_by')->references('id')->on('users');
        });
    }
};