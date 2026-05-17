<?php

declare(strict_types=1);

namespace App\Enums;

enum CreditStatus: string
{
    case ACTIVE = 'active';
    case PARTIAL = 'partial';
    case PAID = 'paid';
    case OVERDUE = 'overdue';
    case CANCELLED = 'cancelled';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function openStatuses(): array
    {
        return [self::ACTIVE->value, self::PARTIAL->value, self::OVERDUE->value];
    }

    public function label(): string
    {
        return match ($this) {
            self::ACTIVE => 'Active',
            self::PARTIAL => 'Partially Paid',
            self::PAID => 'Paid',
            self::OVERDUE => 'Overdue',
            self::CANCELLED => 'Cancelled',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::ACTIVE => 'badge bg-info',
            self::PARTIAL => 'badge bg-warning',
            self::PAID => 'badge bg-success',
            self::OVERDUE => 'badge bg-danger',
            self::CANCELLED => 'badge bg-secondary',
        };
    }
}
