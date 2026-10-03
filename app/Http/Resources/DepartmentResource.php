<?php

namespace App\Http\Resources;

use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DepartmentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $department = $this->resource;

        if (! $department instanceof Department) {
            return [];
        }

        return [
            'id' => $department->id,
            'name' => $department->name,
        ];
    }
}
