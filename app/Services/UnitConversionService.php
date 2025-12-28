<?php

namespace App\Services;

class UnitConversionService
{
    /**
     * Standard unit conversions to base units
     */
    private static array $conversions = [
        // Weight conversions (to grams)
        'weight' => [
            'ton' => 1000000,
            'kg' => 1000,
            'g' => 1,
            'mg' => 0.001,
            'lb' => 453.592,
            'oz' => 28.3495,
        ],
        
        // Volume conversions (to milliliters)
        'volume' => [
            'liter' => 1000,
            'ml' => 1,
            'gallon' => 3785.41,
            'cup' => 236.588,
        ],
        
        // Length conversions (to millimeters)
        'length' => [
            'meter' => 1000,
            'cm' => 10,
            'mm' => 1,
            'inch' => 25.4,
            'ft' => 304.8,
        ],
    ];

    /**
     * Get available sale units for an item based on its base unit
     */
    public static function getAvailableSaleUnits(string $baseUnit): array
    {
        $unitType = self::getUnitType($baseUnit);
        
        return match($unitType) {
            'weight' => [
                'kg' => 'Kilogram',
                'g' => 'Gram',
                '500g' => '500 Grams',
                '250g' => '250 Grams',
            ],
            'volume' => [
                'liter' => 'Liter',
                'ml' => 'Milliliter',
                '500ml' => '500ml',
                '250ml' => '250ml',
            ],
            default => ['each' => 'Each']
        };
    }

    /**
     * Convert quantity from one unit to another
     */
    public static function convert(float $quantity, string $fromUnit, string $toUnit): float
    {
        if ($fromUnit === $toUnit) {
            return $quantity;
        }

        $unitType = self::getUnitType($fromUnit);
        
        if (!isset(self::$conversions[$unitType])) {
            return $quantity; // No conversion available
        }

        $conversions = self::$conversions[$unitType];
        
        // Handle special cases like 500g, 250g
        $fromMultiplier = self::getUnitMultiplier($fromUnit, $conversions);
        $toMultiplier = self::getUnitMultiplier($toUnit, $conversions);
        
        if ($fromMultiplier === null || $toMultiplier === null) {
            return $quantity;
        }

        // Convert to base unit, then to target unit
        $baseQuantity = $quantity * $fromMultiplier;
        return $baseQuantity / $toMultiplier;
    }

    /**
     * Calculate price per unit based on base price and conversions
     */
    public static function calculateUnitPrice(float $basePrice, string $baseUnit, string $saleUnit, int $baseUnitQuantity = 1): float
    {
        if ($baseUnit === $saleUnit) {
            return $basePrice / $baseUnitQuantity;
        }

        // Convert 1 base unit to sale units
        $unitsPerBase = self::convert($baseUnitQuantity, $baseUnit, $saleUnit);
        
        if ($unitsPerBase <= 0) {
            return 0;
        }

        return $basePrice / $unitsPerBase;
    }

    /**
     * Get available stock in different units
     */
    public static function getStockInUnit(float $stockInPieces, int $unitQuantity, string $baseUnit, string $targetUnit): float
    {
        // Total base units available
        $totalBaseUnits = $stockInPieces * $unitQuantity;
        
        // Convert to target unit
        return self::convert($totalBaseUnits, $baseUnit, $targetUnit);
    }

    private static function getUnitType(string $unit): string
    {
        foreach (self::$conversions as $type => $units) {
            if (isset($units[$unit]) || self::isSpecialUnit($unit, $type)) {
                return $type;
            }
        }
        return 'count';
    }

    private static function isSpecialUnit(string $unit, string $type): bool
    {
        $specialUnits = [
            'weight' => ['500g', '250g', '100g'],
            'volume' => ['500ml', '250ml', '100ml'],
        ];

        return isset($specialUnits[$type]) && in_array($unit, $specialUnits[$type]);
    }

    private static function getUnitMultiplier(string $unit, array $conversions): ?float
    {
        if (isset($conversions[$unit])) {
            return $conversions[$unit];
        }

        // Handle special units like 500g, 250g
        if (preg_match('/^(\d+)(g|ml)$/', $unit, $matches)) {
            $amount = (float)$matches[1];
            $baseUnit = $matches[2];
            
            if (isset($conversions[$baseUnit])) {
                return $conversions[$baseUnit] * $amount;
            }
        }

        return null;
    }
}