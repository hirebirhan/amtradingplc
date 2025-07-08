<?php

namespace Database\Seeders;

use App\Models\BankAccount;
use App\Models\Branch;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class BankAccountSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Make sure we have at least one branch
        $branch = Branch::first();
        if (!$branch) {
            $branch = Branch::create([
                'name' => 'Main Branch',
                'code' => 'MAIN',
                'address' => 'Addis Ababa, Ethiopia',
                'phone' => '+251-111-222-333',
                'email' => 'main@stock360.com',
                'is_active' => true,
            ]);
        }

        // Create bank accounts
        $accounts = [
            [
                'account_name' => 'Cash Drawer',
                'account_number' => 'CASH-001',
                'bank_name' => 'Cash Drawer',
                'branch_name' => 'Main Office',
                'balance' => 50000,
                'currency' => 'ETB',
                'is_active' => true,
                'is_default' => true,
                'branch_id' => $branch->id,
                'notes' => 'Virtual cash drawer for cash transactions',
            ],
            [
                'account_name' => 'Commercial Bank of Ethiopia',
                'account_number' => '1000123456789',
                'bank_name' => 'Commercial Bank of Ethiopia',
                'branch_name' => 'Bole Branch',
                'swift_code' => 'CBETETAA',
                'balance' => 100000,
                'currency' => 'ETB',
                'is_active' => true,
                'is_default' => false,
                'branch_id' => $branch->id,
                'notes' => 'Main business account',
            ],
            [
                'account_name' => 'Dashen Bank',
                'account_number' => '2000123456789',
                'bank_name' => 'Dashen Bank',
                'branch_name' => 'Addis Ababa',
                'swift_code' => 'DASHETAA',
                'balance' => 75000,
                'currency' => 'ETB',
                'is_active' => true,
                'is_default' => false,
                'branch_id' => $branch->id,
                'notes' => 'Secondary business account',
            ],
        ];

        foreach ($accounts as $account) {
            // Only create if it doesn't already exist
            if (!BankAccount::where('account_number', $account['account_number'])->exists()) {
                BankAccount::create($account);
            }
        }
    }
}
