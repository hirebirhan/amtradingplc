<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\BankAccount;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class BankService
{
    /**
     * Get all active bank accounts
     */
    public function getActiveBankAccounts(): Collection
    {
        return Cache::remember('active_bank_accounts', 300, function () {
            return BankAccount::active()
                ->with(['branch', 'warehouse'])
                ->orderBy('bank_name')
                ->orderBy('account_name')
                ->get();
        });
    }

    /**
     * Get bank accounts for dropdown selection
     */
    public function getBankAccountsForDropdown(): Collection
    {
        return $this->getActiveBankAccounts()->map(function ($account) {
            return [
                'id' => $account->id,
                'name' => $account->bank_name . ' - ' . $account->account_name . ' (' . $account->account_number . ')',
                'bank_name' => $account->bank_name,
                'account_name' => $account->account_name,
                'account_number' => $account->account_number,
                'location' => $account->location_name,
                'currency' => $account->currency,
            ];
        });
    }

    /**
     * Get unique bank names for filtering
     */
    public function getBankNames(): Collection
    {
        return Cache::remember('bank_names', 300, function () {
            return BankAccount::active()
                ->distinct()
                ->pluck('bank_name')
                ->sort();
        });
    }

    /**
     * Get default bank account
     */
    public function getDefaultBankAccount(): ?BankAccount
    {
        return Cache::remember('default_bank_account', 300, function () {
            return BankAccount::default()->active()->first();
        });
    }

    /**
     * Clear bank-related cache
     */
    public function clearCache(): void
    {
        Cache::forget('active_bank_accounts');
        Cache::forget('bank_names');
        Cache::forget('default_bank_account');
    }

    /**
     * Get bank account by ID with validation
     */
    public function getBankAccount(int $id): ?BankAccount
    {
        return BankAccount::active()->find($id);
    }

    /**
     * Validate bank account exists and is active
     */
    public function validateBankAccount(int $id): bool
    {
        return BankAccount::active()->where('id', $id)->exists();
    }

    /**
     * Get list of Ethiopian banks (single source of truth)
     */
    public function getEthiopianBanks(): array
    {
        return [
            'Abay Bank',
            'Addis International Bank',
            'Awash Bank',
            'Bank of Abyssinia',
            'Berhan International Bank',
            'Bunna International Bank',
            'Commercial Bank of Ethiopia',
            'Cooperative Bank of Oromia',
            'Dashen Bank',
            'Debub Global Bank',
            'Development Bank of Ethiopia',
            'Enat Bank',
            'Lion International Bank',
            'Nib International Bank',
            'Oromia International Bank',
            'United Bank',
            'Wegagen Bank',
            'Zemen Bank',
        ];
    }
} 