<?php

namespace App\Enums;

enum PurchaseStatus: string
{
    case DRAFT = 'draft';
    case CONFIRMED = 'confirmed';
    case RECEIVED = 'received';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match($this) {
            self::DRAFT => 'Draft',
            self::CONFIRMED => 'Confirmed',
            self::RECEIVED => 'Received',
            self::CANCELLED => 'Cancelled',
        };
    }

    public function badgeClass(): string
    {
        return match($this) {
            self::DRAFT => 'badge bg-secondary rounded-1',
            self::CONFIRMED => 'badge bg-primary rounded-1',
            self::RECEIVED => 'badge bg-success rounded-1',
            self::CANCELLED => 'badge bg-danger rounded-1',
        };
    }

    public function description(): string
    {
        return match($this) {
            self::DRAFT => 'Purchase created but not confirmed',
            self::CONFIRMED => 'Purchase approved, awaiting goods',
            self::RECEIVED => 'Goods received, stock updated',
            self::CANCELLED => 'Purchase cancelled',
        };
    }
}