<?php

namespace App\Enums;

enum UserRole: string
{
    case SUPER_ADMIN = 'SuperAdmin';
    case BRANCH_MANAGER = 'BranchManager';
    case WAREHOUSE_MANAGER = 'WarehouseManager';
    case ACCOUNTANT = 'Accountant';
    case CUSTOMER_SERVICE = 'CustomerService';
    case SALES = 'Sales';
    case PURCHASE_OFFICER = 'PurchaseOfficer';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function names(): array
    {
        return array_column(self::cases(), 'name');
    }

    public function label(): string
    {
        return match($this) {
            self::SUPER_ADMIN => 'Super Administrator',
            self::BRANCH_MANAGER => 'Branch Manager',
            self::WAREHOUSE_MANAGER => 'Warehouse Manager',
            self::ACCOUNTANT => 'Accountant',
            self::CUSTOMER_SERVICE => 'Customer Service',
            self::SALES => 'Sales',
            self::PURCHASE_OFFICER => 'Purchase Officer',
        };
    }

    public static function toArray(): array
    {
        $roles = [];
        foreach (self::cases() as $role) {
            $roles[$role->value] = $role->label();
        }
        return $roles;
    }
}
