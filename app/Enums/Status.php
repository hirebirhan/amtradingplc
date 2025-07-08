<?php

namespace App\Enums;

enum Status: string
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
    case ON_LEAVE = 'on_leave';

    /**
     * Get all status values as an array
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get all statuses as an array with name => value pairs
     */
    public static function toArray(): array
    {
        $statuses = [];
        foreach (self::cases() as $status) {
            $statuses[$status->value] = match ($status) {
                self::ACTIVE => 'Active',
                self::INACTIVE => 'Inactive',
                self::ON_LEAVE => 'On Leave',
            };
        }
        return $statuses;
    }

    /**
     * Get status label
     */
    public function label(): string
    {
        return match ($this) {
            self::ACTIVE => 'Active',
            self::INACTIVE => 'Inactive',
            self::ON_LEAVE => 'On Leave',
        };
    }

    /**
     * Get status badge class
     */
    public function badgeClass(): string
    {
        return match ($this) {
            self::ACTIVE => 'bg-success',
            self::INACTIVE => 'bg-danger',
            self::ON_LEAVE => 'bg-warning',
        };
    }
} 