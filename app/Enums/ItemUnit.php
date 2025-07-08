<?php

declare(strict_types=1);

namespace App\Enums;

enum ItemUnit: string
{
    case PIECE = 'piece';
    case KILOGRAM = 'kg';
    case GRAM = 'g';
    case LITER = 'L';
    case MILLILITER = 'ml';
    case METER = 'm';
    case CENTIMETER = 'cm';
    case PACK = 'pack';
    case BOX = 'box';
    case BOTTLE = 'bottle';
    case CAN = 'can';
    case BAG = 'bag';
    case ROLL = 'roll';
    case SET = 'set';
    case PAIR = 'pair';
    case DOZEN = 'dozen';
    case UNIT = 'unit';

    /**
     * Get all item unit values as array
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get display label for each item unit
     */
    public function label(): string
    {
        return match($this) {
            self::PIECE => 'Piece',
            self::KILOGRAM => 'Kilogram (kg)',
            self::GRAM => 'Gram (g)',
            self::LITER => 'Liter (L)',
            self::MILLILITER => 'Milliliter (ml)',
            self::METER => 'Meter (m)',
            self::CENTIMETER => 'Centimeter (cm)',
            self::PACK => 'Pack',
            self::BOX => 'Box',
            self::BOTTLE => 'Bottle',
            self::CAN => 'Can',
            self::BAG => 'Bag',
            self::ROLL => 'Roll',
            self::SET => 'Set',
            self::PAIR => 'Pair',
            self::DOZEN => 'Dozen',
            self::UNIT => 'Unit',
        };
    }

    /**
     * Get short label for each item unit (without parentheses)
     */
    public function shortLabel(): string
    {
        return match($this) {
            self::PIECE => 'Piece',
            self::KILOGRAM => 'Kilogram',
            self::GRAM => 'Gram',
            self::LITER => 'Liter',
            self::MILLILITER => 'Milliliter',
            self::METER => 'Meter',
            self::CENTIMETER => 'Centimeter',
            self::PACK => 'Pack',
            self::BOX => 'Box',
            self::BOTTLE => 'Bottle',
            self::CAN => 'Can',
            self::BAG => 'Bag',
            self::ROLL => 'Roll',
            self::SET => 'Set',
            self::PAIR => 'Pair',
            self::DOZEN => 'Dozen',
            self::UNIT => 'Unit',
        };
    }

    /**
     * Get default item unit
     */
    public static function default(): self
    {
        return self::PIECE;
    }
} 