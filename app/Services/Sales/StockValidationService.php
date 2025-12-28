<?php

namespace App\Services\Sales;

use App\Models\Item;

class StockValidationService
{
    private ItemSelectionService $itemService;

    public function __construct()
    {
        $this->itemService = new ItemSelectionService();
    }

    public function validateStock(Item $item, float $requestedQuantity, ?int $warehouseId): array
    {
        $availableStock = $this->itemService->getItemStock($item, $warehouseId);
        
        if ($availableStock <= 0) {
            return [
                'valid' => false,
                'type' => 'out_of_stock',
                'data' => [
                    'name' => $item->name,
                    'stock' => $availableStock
                ]
            ];
        }
        
        if ($requestedQuantity > $availableStock) {
            return [
                'valid' => false,
                'type' => 'insufficient',
                'data' => [
                    'name' => $item->name,
                    'available' => $availableStock,
                    'requested' => $requestedQuantity,
                    'deficit' => $requestedQuantity - $availableStock
                ]
            ];
        }
        
        return [
            'valid' => true,
            'available_stock' => $availableStock
        ];
    }

    public function validateItemPrice(Item $item, float $sellingPrice, string $saleMethod): ?string
    {
        return $this->itemService->validateItemPrice($item, $sellingPrice, $saleMethod);
    }
}