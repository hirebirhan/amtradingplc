<?php

namespace App\OpenApi;

use OpenApi\Attributes as OA;

#[OA\Info(
    title: 'AM Trading PLC API',
    version: '1.0.0',
    description: 'Inventory & sales management API for AM Trading PLC. All endpoints require Bearer token authentication except `POST /auth/login`.'
)]
#[OA\Server(url: '/api/v1', description: 'API v1')]
#[OA\SecurityScheme(
    securityScheme: 'sanctum',
    type: 'http',
    scheme: 'bearer',
    bearerFormat: 'Token',
    description: 'Laravel Sanctum token — obtain via POST /auth/login'
)]
#[OA\Tag(name: 'Auth', description: 'Authentication')]
#[OA\Tag(name: 'Branches', description: 'Branch management')]
#[OA\Tag(name: 'Warehouses', description: 'Warehouse management')]
#[OA\Tag(name: 'Categories', description: 'Item categories')]
#[OA\Tag(name: 'Items', description: 'Inventory items')]
#[OA\Tag(name: 'Customers', description: 'Customer management')]
#[OA\Tag(name: 'Suppliers', description: 'Supplier management')]
#[OA\Tag(name: 'Users', description: 'User management')]
#[OA\Tag(name: 'Roles', description: 'Role & permission management')]
#[OA\Tag(name: 'Employees', description: 'Employee management')]
#[OA\Tag(name: 'BankAccounts', description: 'Bank account management')]
#[OA\Tag(name: 'Purchases', description: 'Purchase orders')]
#[OA\Tag(name: 'Sales', description: 'Sales orders')]
#[OA\Tag(name: 'Credits', description: 'Credit & closing-offer management')]
#[OA\Tag(name: 'Transfers', description: 'Stock transfer workflow')]
#[OA\Tag(name: 'Stock', description: 'Current stock levels')]
#[OA\Tag(name: 'StockReservations', description: 'Stock reservation management')]
#[OA\Tag(name: 'StockReports', description: 'Stock reporting & export')]
#[OA\Tag(name: 'Expenses', description: 'Expense management')]
#[OA\Tag(name: 'PriceHistory', description: 'Item price history')]
#[OA\Tag(name: 'Dashboard', description: 'Dashboard statistics & charts')]
#[OA\Tag(name: 'Reports', description: 'Financial & operational reports')]

// ── Schemas ─────────────────────────────────────────────────────────────────

#[OA\Schema(
    schema: 'Branch',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'name', type: 'string', example: 'Main Branch'),
        new OA\Property(property: 'address', type: 'string', nullable: true),
        new OA\Property(property: 'phone', type: 'string', nullable: true),
        new OA\Property(property: 'is_active', type: 'boolean', example: true),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
    ]
)]
#[OA\Schema(
    schema: 'Warehouse',
    properties: [
        new OA\Property(property: 'id', type: 'integer'),
        new OA\Property(property: 'name', type: 'string'),
        new OA\Property(property: 'type', type: 'string', enum: ['main', 'secondary', 'transit']),
        new OA\Property(property: 'location', type: 'string', nullable: true),
        new OA\Property(property: 'is_active', type: 'boolean'),
        new OA\Property(property: 'branch', ref: '#/components/schemas/Branch', nullable: true),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
    ]
)]
#[OA\Schema(
    schema: 'Category',
    properties: [
        new OA\Property(property: 'id', type: 'integer'),
        new OA\Property(property: 'name', type: 'string', example: 'Electronics'),
        new OA\Property(property: 'code', type: 'string', example: 'ELEC'),
        new OA\Property(property: 'parent_id', type: 'integer', nullable: true),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
    ]
)]
#[OA\Schema(
    schema: 'Item',
    properties: [
        new OA\Property(property: 'id', type: 'integer'),
        new OA\Property(property: 'name', type: 'string'),
        new OA\Property(property: 'sku', type: 'string'),
        new OA\Property(property: 'barcode', type: 'string', nullable: true),
        new OA\Property(property: 'unit', type: 'string', example: 'piece'),
        new OA\Property(property: 'unit_quantity', type: 'integer', example: 1),
        new OA\Property(property: 'cost_price', type: 'number', format: 'float'),
        new OA\Property(property: 'selling_price', type: 'number', format: 'float'),
        new OA\Property(property: 'min_stock_level', type: 'integer'),
        new OA\Property(property: 'is_active', type: 'boolean'),
        new OA\Property(property: 'category', ref: '#/components/schemas/Category', nullable: true),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
    ]
)]
#[OA\Schema(
    schema: 'Customer',
    properties: [
        new OA\Property(property: 'id', type: 'integer'),
        new OA\Property(property: 'name', type: 'string'),
        new OA\Property(property: 'email', type: 'string', nullable: true),
        new OA\Property(property: 'phone', type: 'string', nullable: true),
        new OA\Property(property: 'address', type: 'string', nullable: true),
        new OA\Property(property: 'is_active', type: 'boolean'),
        new OA\Property(property: 'branch', ref: '#/components/schemas/Branch', nullable: true),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
    ]
)]
#[OA\Schema(
    schema: 'Supplier',
    properties: [
        new OA\Property(property: 'id', type: 'integer'),
        new OA\Property(property: 'name', type: 'string'),
        new OA\Property(property: 'email', type: 'string', nullable: true),
        new OA\Property(property: 'phone', type: 'string', nullable: true),
        new OA\Property(property: 'address', type: 'string', nullable: true),
        new OA\Property(property: 'is_active', type: 'boolean'),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
    ]
)]
#[OA\Schema(
    schema: 'User',
    properties: [
        new OA\Property(property: 'id', type: 'integer'),
        new OA\Property(property: 'name', type: 'string'),
        new OA\Property(property: 'email', type: 'string', format: 'email'),
        new OA\Property(property: 'phone', type: 'string', nullable: true),
        new OA\Property(property: 'is_active', type: 'boolean'),
        new OA\Property(property: 'branch', ref: '#/components/schemas/Branch', nullable: true),
        new OA\Property(property: 'roles', type: 'array', items: new OA\Items(type: 'string'), example: ['BranchManager']),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
    ]
)]
#[OA\Schema(
    schema: 'Role',
    properties: [
        new OA\Property(property: 'id', type: 'integer'),
        new OA\Property(property: 'name', type: 'string', example: 'BranchManager'),
        new OA\Property(property: 'guard_name', type: 'string', example: 'sanctum'),
        new OA\Property(property: 'permissions', type: 'array', items: new OA\Items(type: 'string')),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
    ]
)]
#[OA\Schema(
    schema: 'Employee',
    properties: [
        new OA\Property(property: 'id', type: 'integer'),
        new OA\Property(property: 'name', type: 'string'),
        new OA\Property(property: 'email', type: 'string', nullable: true),
        new OA\Property(property: 'phone', type: 'string', nullable: true),
        new OA\Property(property: 'department', type: 'string', nullable: true),
        new OA\Property(property: 'position', type: 'string', nullable: true),
        new OA\Property(property: 'is_active', type: 'boolean'),
        new OA\Property(property: 'branch', ref: '#/components/schemas/Branch', nullable: true),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
    ]
)]
#[OA\Schema(
    schema: 'BankAccount',
    properties: [
        new OA\Property(property: 'id', type: 'integer'),
        new OA\Property(property: 'bank_name', type: 'string'),
        new OA\Property(property: 'account_name', type: 'string'),
        new OA\Property(property: 'account_number', type: 'string'),
        new OA\Property(property: 'is_active', type: 'boolean'),
        new OA\Property(property: 'branch', ref: '#/components/schemas/Branch', nullable: true),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
    ]
)]
#[OA\Schema(
    schema: 'PurchaseItem',
    properties: [
        new OA\Property(property: 'id', type: 'integer'),
        new OA\Property(property: 'item_id', type: 'integer'),
        new OA\Property(property: 'item_name', type: 'string'),
        new OA\Property(property: 'quantity', type: 'integer'),
        new OA\Property(property: 'unit_cost', type: 'number', format: 'float'),
        new OA\Property(property: 'subtotal', type: 'number', format: 'float'),
    ]
)]
#[OA\Schema(
    schema: 'Purchase',
    properties: [
        new OA\Property(property: 'id', type: 'integer'),
        new OA\Property(property: 'reference_no', type: 'string', example: 'PO-2024-0001'),
        new OA\Property(property: 'supplier', ref: '#/components/schemas/Supplier', nullable: true),
        new OA\Property(property: 'branch', ref: '#/components/schemas/Branch', nullable: true),
        new OA\Property(property: 'warehouse', ref: '#/components/schemas/Warehouse', nullable: true),
        new OA\Property(property: 'purchase_date', type: 'string', format: 'date'),
        new OA\Property(property: 'status', type: 'string', enum: ['draft', 'confirmed', 'received', 'cancelled']),
        new OA\Property(property: 'payment_method', type: 'string', enum: ['cash', 'bank_transfer', 'telebirr', 'credit_advance', 'full_credit']),
        new OA\Property(property: 'total_amount', type: 'number', format: 'float'),
        new OA\Property(property: 'paid_amount', type: 'number', format: 'float'),
        new OA\Property(property: 'due_amount', type: 'number', format: 'float'),
        new OA\Property(property: 'note', type: 'string', nullable: true),
        new OA\Property(property: 'items', type: 'array', items: new OA\Items(ref: '#/components/schemas/PurchaseItem')),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
    ]
)]
#[OA\Schema(
    schema: 'SaleItem',
    properties: [
        new OA\Property(property: 'id', type: 'integer'),
        new OA\Property(property: 'item_id', type: 'integer'),
        new OA\Property(property: 'item_name', type: 'string'),
        new OA\Property(property: 'quantity', type: 'integer'),
        new OA\Property(property: 'unit_price', type: 'number', format: 'float'),
        new OA\Property(property: 'subtotal', type: 'number', format: 'float'),
    ]
)]
#[OA\Schema(
    schema: 'Sale',
    properties: [
        new OA\Property(property: 'id', type: 'integer'),
        new OA\Property(property: 'reference_no', type: 'string', example: 'SO-2024-0001'),
        new OA\Property(property: 'customer', ref: '#/components/schemas/Customer', nullable: true),
        new OA\Property(property: 'branch', ref: '#/components/schemas/Branch', nullable: true),
        new OA\Property(property: 'sale_date', type: 'string', format: 'date'),
        new OA\Property(property: 'payment_method', type: 'string', enum: ['cash', 'bank_transfer', 'telebirr', 'credit_advance', 'full_credit']),
        new OA\Property(property: 'payment_status', type: 'string', enum: ['paid', 'partial', 'pending', 'due']),
        new OA\Property(property: 'total_amount', type: 'number', format: 'float'),
        new OA\Property(property: 'paid_amount', type: 'number', format: 'float'),
        new OA\Property(property: 'advance_amount', type: 'number', format: 'float', nullable: true),
        new OA\Property(property: 'due_amount', type: 'number', format: 'float'),
        new OA\Property(property: 'note', type: 'string', nullable: true),
        new OA\Property(property: 'items', type: 'array', items: new OA\Items(ref: '#/components/schemas/SaleItem')),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
    ]
)]
#[OA\Schema(
    schema: 'CreditPayment',
    properties: [
        new OA\Property(property: 'id', type: 'integer'),
        new OA\Property(property: 'amount', type: 'number', format: 'float'),
        new OA\Property(property: 'payment_method', type: 'string', enum: ['cash', 'bank_transfer', 'telebirr', 'credit_card', 'check', 'other']),
        new OA\Property(property: 'note', type: 'string', nullable: true),
        new OA\Property(property: 'payment_date', type: 'string', format: 'date'),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
    ]
)]
#[OA\Schema(
    schema: 'Credit',
    properties: [
        new OA\Property(property: 'id', type: 'integer'),
        new OA\Property(property: 'reference_type', type: 'string', enum: ['sale', 'purchase']),
        new OA\Property(property: 'reference_id', type: 'integer'),
        new OA\Property(property: 'reference_no', type: 'string'),
        new OA\Property(property: 'customer', ref: '#/components/schemas/Customer', nullable: true),
        new OA\Property(property: 'branch', ref: '#/components/schemas/Branch', nullable: true),
        new OA\Property(property: 'total_amount', type: 'number', format: 'float'),
        new OA\Property(property: 'paid_amount', type: 'number', format: 'float'),
        new OA\Property(property: 'balance', type: 'number', format: 'float'),
        new OA\Property(property: 'status', type: 'string', enum: ['active', 'partial', 'paid', 'overdue', 'cancelled']),
        new OA\Property(property: 'due_date', type: 'string', format: 'date', nullable: true),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
    ]
)]
#[OA\Schema(
    schema: 'TransferItem',
    properties: [
        new OA\Property(property: 'id', type: 'integer'),
        new OA\Property(property: 'item_id', type: 'integer'),
        new OA\Property(property: 'item_name', type: 'string'),
        new OA\Property(property: 'quantity', type: 'integer'),
        new OA\Property(property: 'unit', type: 'string'),
    ]
)]
#[OA\Schema(
    schema: 'Transfer',
    properties: [
        new OA\Property(property: 'id', type: 'integer'),
        new OA\Property(property: 'reference_code', type: 'string', example: 'TRF-2024-0001'),
        new OA\Property(property: 'status', type: 'string', enum: ['pending', 'approved', 'in_transit', 'completed', 'rejected', 'cancelled']),
        new OA\Property(property: 'source_type', type: 'string', enum: ['branch', 'warehouse']),
        new OA\Property(property: 'source_id', type: 'integer'),
        new OA\Property(property: 'source_name', type: 'string'),
        new OA\Property(property: 'destination_type', type: 'string', enum: ['branch', 'warehouse']),
        new OA\Property(property: 'destination_id', type: 'integer'),
        new OA\Property(property: 'destination_name', type: 'string'),
        new OA\Property(property: 'note', type: 'string', nullable: true),
        new OA\Property(property: 'date_initiated', type: 'string', format: 'date-time'),
        new OA\Property(property: 'approved_at', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'items', type: 'array', items: new OA\Items(ref: '#/components/schemas/TransferItem')),
        new OA\Property(property: 'creator', ref: '#/components/schemas/User', nullable: true),
        new OA\Property(property: 'approved_by', ref: '#/components/schemas/User', nullable: true),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
    ]
)]
#[OA\Schema(
    schema: 'Stock',
    properties: [
        new OA\Property(property: 'id', type: 'integer'),
        new OA\Property(property: 'item', ref: '#/components/schemas/Item'),
        new OA\Property(property: 'branch', ref: '#/components/schemas/Branch', nullable: true),
        new OA\Property(property: 'warehouse', ref: '#/components/schemas/Warehouse', nullable: true),
        new OA\Property(property: 'quantity', type: 'integer'),
        new OA\Property(property: 'unit', type: 'string'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
    ]
)]
#[OA\Schema(
    schema: 'StockHistory',
    properties: [
        new OA\Property(property: 'id', type: 'integer'),
        new OA\Property(property: 'item', ref: '#/components/schemas/Item'),
        new OA\Property(property: 'branch', ref: '#/components/schemas/Branch', nullable: true),
        new OA\Property(property: 'warehouse', ref: '#/components/schemas/Warehouse', nullable: true),
        new OA\Property(property: 'type', type: 'string', enum: ['in', 'out', 'adjustment', 'transfer']),
        new OA\Property(property: 'quantity', type: 'integer'),
        new OA\Property(property: 'balance_after', type: 'integer'),
        new OA\Property(property: 'note', type: 'string', nullable: true),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
    ]
)]
#[OA\Schema(
    schema: 'StockReservation',
    properties: [
        new OA\Property(property: 'id', type: 'integer'),
        new OA\Property(property: 'item', ref: '#/components/schemas/Item'),
        new OA\Property(property: 'quantity', type: 'integer'),
        new OA\Property(property: 'status', type: 'string', enum: ['active', 'released', 'expired']),
        new OA\Property(property: 'expires_at', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
    ]
)]
#[OA\Schema(
    schema: 'Expense',
    properties: [
        new OA\Property(property: 'id', type: 'integer'),
        new OA\Property(property: 'reference_no', type: 'string'),
        new OA\Property(property: 'category', type: 'string'),
        new OA\Property(property: 'amount', type: 'number', format: 'float'),
        new OA\Property(property: 'payment_method', type: 'string'),
        new OA\Property(property: 'expense_date', type: 'string', format: 'date'),
        new OA\Property(property: 'note', type: 'string', nullable: true),
        new OA\Property(property: 'is_recurring', type: 'boolean'),
        new OA\Property(property: 'branch', ref: '#/components/schemas/Branch', nullable: true),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
    ]
)]
#[OA\Schema(
    schema: 'PriceHistory',
    properties: [
        new OA\Property(property: 'id', type: 'integer'),
        new OA\Property(property: 'item', ref: '#/components/schemas/Item'),
        new OA\Property(property: 'old_cost_price', type: 'number', format: 'float', nullable: true),
        new OA\Property(property: 'new_cost_price', type: 'number', format: 'float', nullable: true),
        new OA\Property(property: 'old_selling_price', type: 'number', format: 'float', nullable: true),
        new OA\Property(property: 'new_selling_price', type: 'number', format: 'float', nullable: true),
        new OA\Property(property: 'changed_by', ref: '#/components/schemas/User', nullable: true),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
    ]
)]
#[OA\Schema(
    schema: 'Pagination',
    properties: [
        new OA\Property(property: 'current_page', type: 'integer'),
        new OA\Property(property: 'last_page', type: 'integer'),
        new OA\Property(property: 'per_page', type: 'integer'),
        new OA\Property(property: 'total', type: 'integer'),
    ]
)]
#[OA\Schema(
    schema: 'ValidationError',
    properties: [
        new OA\Property(property: 'message', type: 'string', example: 'The given data was invalid.'),
        new OA\Property(
            property: 'errors',
            type: 'object',
            additionalProperties: new OA\AdditionalProperties(
                type: 'array',
                items: new OA\Items(type: 'string')
            )
        ),
    ]
)]
class OpenApiSpec {}
