<?php

declare(strict_types=1);

namespace App\Enums;

enum CreditType: string
{
    case RECEIVABLE = 'receivable';
    case PAYABLE = 'payable';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function label(): string
    {
        return match ($this) {
            self::RECEIVABLE => 'Receivable',
            self::PAYABLE => 'Payable',
        };
    }
}
