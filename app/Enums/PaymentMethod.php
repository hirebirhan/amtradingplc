<?php

declare(strict_types=1);

namespace App\Enums;

enum PaymentMethod: string
{
    case CASH = 'cash';
    case BANK_TRANSFER = 'bank_transfer';
    case TELEBIRR = 'telebirr';
    case CREDIT_ADVANCE = 'credit_advance';
    case FULL_CREDIT = 'full_credit';
    case CREDIT_FULL = 'credit_full'; // For legacy/compatibility
    // Add more as needed (e.g., CHECK, MOBILE_MONEY, etc.)

    /**
     * Get all payment method values as array
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get ordered payment methods for purchases (Full Credit first, then Credit with Advance, etc.)
     */
    public static function forPurchases(): array
    {
        return [
            self::FULL_CREDIT,
            self::CREDIT_ADVANCE,
            self::CASH,
            self::BANK_TRANSFER,
            self::TELEBIRR,
        ];
    }

    /**
     * Get ordered payment methods for sales (Cash first, then Bank Transfer, etc.)
     */
    public static function forSales(): array
    {
        return [
            self::CASH,
            self::BANK_TRANSFER,
            self::TELEBIRR,
            self::CREDIT_ADVANCE,
            self::FULL_CREDIT,
        ];
    }

    /**
     * Get default payment method for purchases
     */
    public static function defaultForPurchases(): self
    {
        return self::FULL_CREDIT;
    }

    /**
     * Get default payment method for sales
     */
    public static function defaultForSales(): self
    {
        return self::CASH;
    }

    /**
     * Get display label for each payment method
     */
    public function label(): string
    {
        return match($this) {
            self::CASH => 'Cash Payment',
            self::BANK_TRANSFER => 'Bank Transfer',
            self::TELEBIRR => 'Telebirr',
            self::CREDIT_ADVANCE => 'Credit with Advance',
            self::FULL_CREDIT, self::CREDIT_FULL => 'Full Credit',
        };
    }
} 