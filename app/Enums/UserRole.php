<?php

declare(strict_types=1);

namespace App\Enums;

enum UserRole: string
{
    case SUPER_ADMIN = 'SuperAdmin';
    case GENERAL_MANAGER = 'GeneralManager';
    case BRANCH_MANAGER = 'BranchManager';
    case WAREHOUSE_MANAGER = 'WarehouseManager';
    case WAREHOUSE_USER = 'WarehouseUser';
    case SALES = 'Sales';
    case ACCOUNTANT = 'Accountant';
    case CUSTOMER_SERVICE = 'CustomerService';
    case PURCHASE_OFFICER = 'PurchaseOfficer';
    case SYSTEM_ADMIN = 'SystemAdmin';
    case MANAGER = 'Manager';

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
            self::GENERAL_MANAGER => 'General Manager',
            self::BRANCH_MANAGER => 'Branch Manager',
            self::WAREHOUSE_MANAGER => 'Warehouse Manager',
            self::WAREHOUSE_USER => 'Warehouse User',
            self::SALES => 'Sales Representative',
            self::ACCOUNTANT => 'Accountant',
            self::CUSTOMER_SERVICE => 'Customer Service',
            self::PURCHASE_OFFICER => 'Purchase Officer',
            self::SYSTEM_ADMIN => 'System Administrator',
            self::MANAGER => 'Manager',
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

    public function isAdmin(): bool
    {
        return in_array($this, [
            self::SUPER_ADMIN,
            self::GENERAL_MANAGER,
            self::SYSTEM_ADMIN,
        ]);
    }

    public function isManager(): bool
    {
        return in_array($this, [
            self::SUPER_ADMIN,
            self::GENERAL_MANAGER,
            self::BRANCH_MANAGER,
            self::WAREHOUSE_MANAGER,
            self::MANAGER,
        ]);
    }
}