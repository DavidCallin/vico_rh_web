<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ContractResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'contract_type' => $this->contract_type,
            'start_date' => $this->start_date->format('Y-m-d'),
            'end_date' => $this->end_date?->format('Y-m-d'),
            'base_salary' => $this->base_salary,
            'pdf_path' => $this->pdf_path,
            'is_active' => $this->end_date === null || $this->end_date->isFuture(),
            
            // Relations
            'employee' => new EmployeeResource($this->whenLoaded('employee')),
            
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
        ];
    }
}