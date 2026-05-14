<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CategoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'name'        => $this->name,
            'code'        => $this->code,
            'slug'        => $this->slug,
            'description' => $this->description,
            'parent_id'   => $this->parent_id,
            'is_active'   => (bool) $this->is_active,
            'parent'      => new CategoryResource($this->whenLoaded('parent')),
            'children'    => CategoryResource::collection($this->whenLoaded('children')),
        ];
    }
}
