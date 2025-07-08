<?php

namespace App\Enums;

enum Department: string
{
    case SALES = 'Sales';
    case FINANCE = 'Finance';
    case HUMAN_RESOURCES = 'Human Resources';
    case OPERATIONS = 'Operations';
    case WAREHOUSE = 'Warehouse';
    case LOGISTICS = 'Logistics';
    case IT = 'Information Technology';
    case CUSTOMER_SERVICE = 'Customer Service';
    case MARKETING = 'Marketing';
    case PROCUREMENT = 'Procurement';

    /**
     * Get all department values as an array
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get all departments as an array with name => value pairs
     */
    public static function toArray(): array
    {
        return array_combine(
            array_column(self::cases(), 'value'),
            array_column(self::cases(), 'value')
        );
    }

    /**
     * Get department label
     */
    public function label(): string
    {
        return $this->value;
    }
} 