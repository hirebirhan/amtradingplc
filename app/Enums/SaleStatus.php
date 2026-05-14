<?php

declare(strict_types=1);

namespace App\Enums;

enum SaleStatus: string
{
    case PENDING = 'pending';
    case COMPLETED = 'completed';
    case CANCELED = 'canceled';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Pending',
            self::COMPLETED => 'Completed',
            self::CANCELED => 'Canceled',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::PENDING => 'badge bg-warning',
            self::COMPLETED => 'badge bg-success',
            self::CANCELED => 'badge bg-danger',
        };
    }
}
