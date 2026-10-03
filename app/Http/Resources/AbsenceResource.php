<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AbsenceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'start_date' => $this->start_date->format('Y-m-d'),
            'end_date' => $this->end_date->format('Y-m-d'),
            'reason' => $this->reason,
            'proof_path' => $this->proof_path, // URL vers le justificatif uploadé
            'status' => $this->status,
            'validation_date' => $this->validation_date?->format('Y-m-d H:i:s'),
            
            // Relations
            'employee' => new EmployeeResource($this->whenLoaded('employee')),
            'validator' => new EmployeeResource($this->whenLoaded('validator')),
            'pay_period' => [
                'id' => $this->payPeriod?->id,
                'month' => $this->payPeriod?->month,
                'year' => $this->payPeriod?->year,
            ],
            
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
        ];
    }
}