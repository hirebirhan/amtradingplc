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
            $table->foreignId('branch_id')->nullable()->after('email_verified_at')->constrained()->nullOnDelete();
            $table->foreignId('warehouse_id')->nullable()->after('branch_id')->constrained()->nullOnDelete();
            $table->string('phone', 20)->nullable()->after('warehouse_id');
            $table->string('position', 50)->nullable()->after('phone');
            $table->boolean('is_active')->default(true)->after('position');
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['branch_id']);
            $table->dropForeign(['warehouse_id']);
            $table->dropColumn(['branch_id', 'warehouse_id', 'phone', 'position', 'is_active']);
            $table->dropSoftDeletes();
        });
    }
};
