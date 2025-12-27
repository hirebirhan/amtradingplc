<?php

namespace App\Enums;

enum UnitType: string
{
    case EACH = 'each';
    case WEIGHT = 'weight';
    case VOLUME = 'volume';
    case LENGTH = 'length';
    case AREA = 'area';
    case COUNT = 'count';

    public function label(): string
    {
        return match($this) {
            self::EACH => 'Each/Piece',
            self::WEIGHT => 'Weight',
            self::VOLUME => 'Volume',
            self::LENGTH => 'Length',
            self::AREA => 'Area',
            self::COUNT => 'Count/Pack',
        };
    }

    public function units(): array
    {
        return match($this) {
            self::EACH => ['each', 'piece', 'item'],
            self::WEIGHT => ['kg', 'g', 'ton', 'lb', 'oz'],
            self::VOLUME => ['liter', 'ml', 'gallon', 'cup'],
            self::LENGTH => ['meter', 'cm', 'mm', 'inch', 'ft'],
            self::AREA => ['sqm', 'sqft', 'acre'],
            self::COUNT => ['pack', 'box', 'case', 'dozen'],
        };
    }

    public static function getUnitType(string $unit): self
    {
        $unit = strtolower(trim($unit));
        
        foreach (self::cases() as $type) {
            if (in_array($unit, $type->units())) {
                return $type;
            }
        }
        
        return self::EACH; // Default fallback
    }

    public function isWeightOrVolume(): bool
    {
        return in_array($this, [self::WEIGHT, self::VOLUME]);
    }

    public function allowsDecimals(): bool
    {
        return in_array($this, [self::WEIGHT, self::VOLUME, self::LENGTH, self::AREA]);
    }
}