<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\CreditPaymentService;
use Illuminate\Console\Command;

class FixClosingPaymentCredits extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'credits:fix-closing-payments {--dry-run : Show what would be fixed without making changes}';

    /**
     * The console command description.
     */
    protected $description = 'Fix credits with closing payments that have incorrect status';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🔧 Fixing closing payment credits...');
        
        $service = new CreditPaymentService();
        
        if ($this->option('dry-run')) {
            $this->warn('DRY RUN MODE - No changes will be made');
        }
        
        $result = $service->fixClosingPaymentCredits();
        
        if ($result['success']) {
            $this->info("✅ Fix completed! Total credits fixed: {$result['total_fixed']}");
            
            if ($result['fixed_credits']) {
                $this->newLine();
                $this->info('📋 Fixed Credits:');
                
                $headers = ['ID', 'Reference', 'Old Amount', 'New Amount', 'Old Status', 'New Status', 'Balance'];
                $rows = [];
                
                foreach ($result['fixed_credits'] as $credit) {
                    $rows[] = [
                        $credit['id'],
                        $credit['reference_no'],
                        number_format((float) $credit['old_amount'], 2) . ' ETB',
                        number_format((float) $credit['new_amount'], 2) . ' ETB',
                        $credit['old_status'],
                        $credit['new_status'],
                        number_format((float) $credit['balance'], 2) . ' ETB'
                    ];
                }
                
                $this->table($headers, $rows);
            }
            
            if ($result['errors']) {
                $this->newLine();
                $this->error('❌ Errors encountered:');
                foreach ($result['errors'] as $error) {
                    $this->error("- Credit ID {$error['credit_id']}: {$error['error']}");
                }
            }
            
            return self::SUCCESS;
        } else {
            $this->error('❌ Failed to fix closing payment credits');
            return self::FAILURE;
        }
    }
} 