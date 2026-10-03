<?php

namespace App\Http\Resources;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CategoryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $category = $this->resource;

        if (! $category instanceof Category) {
            return [];
        }

        return [
            'id' => $category->id,
            'name' => $category->name,
            'code' => $category->code,
        ];
    }
}
