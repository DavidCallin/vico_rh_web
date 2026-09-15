<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LeaveResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'leave_Type' => $this->leave_type,
            'start_date' => $this->start_date->format('Y-m-d'),
            'end_date' => $this->end_date->format('Y-m-d'),
            'status' => $this->status,
            'validation_date' => $this->validation_date?->format('Y-m-d'),
            'reason' => $this->reason,

            //Relations (seulement si chargées)
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
