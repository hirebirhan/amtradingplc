<?php

declare(strict_types=1);

namespace App\Enums;

enum PaymentStatus: string
{
    case PAID = 'paid';
    case PARTIAL = 'partial';
    case PENDING = 'pending';
    case DUE = 'due';

    /**
     * Get all payment status values as array
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get display label for each payment status
     */
    public function label(): string
    {
        return match($this) {
            self::PAID => 'Paid',
            self::PARTIAL => 'Partially Paid',
            self::PENDING => 'Pending',
            self::DUE => 'Due',
        };
    }

    /**
     * Get Bootstrap color class for each payment status
     */
    public function color(): string
    {
        return match($this) {
            self::PAID => 'success',
            self::PARTIAL => 'warning',
            self::PENDING => 'info',
            self::DUE => 'danger',
        };
    }

    /**
     * Get Bootstrap badge class for each payment status
     */
    public function badgeClass(): string
    {
        return "badge bg-{$this->color()} rounded-1";
    }
} 