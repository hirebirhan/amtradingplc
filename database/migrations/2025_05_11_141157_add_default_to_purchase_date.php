<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        try {
            // First check if the purchases table exists
            if (!Schema::hasTable('purchases')) {
                // Table doesn't exist, create a log entry and exit gracefully
                Log::warning('Migration skipped: purchases table does not exist');
                return;
            }

            Schema::table('purchases', function (Blueprint $table) {
                // Now check if the column exists
                if (Schema::hasColumn('purchases', 'purchase_date')) {
                    // MySQL-specific way to set the default value to current date
                    if (config('database.default') === 'mysql') {
                        try {
                            DB::statement('ALTER TABLE purchases MODIFY purchase_date DATE NOT NULL DEFAULT CURRENT_DATE');
                        } catch (\Exception $e) {
                            Log::error('Failed to set DEFAULT CURRENT_DATE: ' . $e->getMessage());
                            // Fallback to the safer approach
                            $table->date('purchase_date')->nullable(false)->change();
                        }
                    } else {
                        // Fallback for other database types if needed
                        $table->date('purchase_date')->default(DB::raw('CURRENT_TIMESTAMP'))->change();
                    }
                } else {
                    Log::warning('Migration skipped: purchase_date column does not exist in purchases table');
                }
            });
        } catch (\Exception $e) {
            Log::error('Migration failed: ' . $e->getMessage());
            // Continue execution, don't block other migrations
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        try {
            // First check if the purchases table exists
            if (!Schema::hasTable('purchases')) {
                Log::warning('Migration rollback skipped: purchases table does not exist');
                return;
            }
            
            Schema::table('purchases', function (Blueprint $table) {
                // Remove the default value
                if (Schema::hasColumn('purchases', 'purchase_date')) {
                    if (config('database.default') === 'mysql') {
                        try {
                            DB::statement('ALTER TABLE purchases MODIFY purchase_date DATE NOT NULL');
                        } catch (\Exception $e) {
                            Log::error('Failed to modify purchase_date: ' . $e->getMessage());
                            // Fallback approach
                            $table->date('purchase_date')->nullable(false)->default(null)->change();
                        }
                    } else {
                        // Fallback for other database types
                        $table->date('purchase_date')->nullable(false)->default(null)->change();
                    }
                } else {
                    Log::warning('Migration rollback skipped: purchase_date column does not exist');
                }
            });
        } catch (\Exception $e) {
            Log::error('Migration rollback failed: ' . $e->getMessage());
            // Continue execution, don't block other migrations
        }
    }
};
