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
        Schema::table('expenses', function (Blueprint $table) {
            // First make the category column nullable for backward compatibility
            $table->string('category')->nullable()->change();
            
            // Add the expense_type_id foreign key
            $table->foreignId('expense_type_id')->nullable()->after('category')
                  ->constrained('expense_types')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->dropForeign(['expense_type_id']);
            $table->dropColumn('expense_type_id');
            $table->string('category')->nullable(false)->change();
        });
    }
};
