<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                      => $this->id,
            'name'                    => $this->name,
            'sku'                     => $this->sku,
            'barcode'                 => $this->barcode,
            'brand'                   => $this->brand,
            'description'             => $this->description,
            'unit'                    => $this->unit,
            'item_unit'               => $this->item_unit,
            'unit_quantity'           => $this->unit_quantity,
            'reorder_level'           => $this->reorder_level,
            'is_active'               => (bool) $this->is_active,
            'prices' => [
                'cost_price'               => (float) $this->cost_price,
                'selling_price'            => (float) $this->selling_price,
                'cost_price_per_unit'      => (float) $this->cost_price_per_unit,
                'selling_price_per_unit'   => (float) $this->selling_price_per_unit,
            ],
            'image_path'   => $this->image_path,
            'category'     => new CategoryResource($this->whenLoaded('category')),
            'stocks'       => StockResource::collection($this->whenLoaded('stocks')),
        ];
    }
}
