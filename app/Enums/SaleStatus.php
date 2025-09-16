<?php

declare(strict_types=1);

namespace App\Enums;

enum SaleStatus: string
{
    case DRAFT = 'draft';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Draft',
            self::COMPLETED => 'Completed',
            self::CANCELLED => 'Cancelled',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::DRAFT => 'badge bg-secondary',
            self::COMPLETED => 'badge bg-success',
            self::CANCELLED => 'badge bg-danger',
        };
    }
}
