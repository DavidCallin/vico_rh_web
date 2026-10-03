<?php

namespace App\Http\Resources;

use App\Http\Resources\PayslipLineResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PayslipResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'generation_date' => $this->generation_date->format('Y-m-d'),
            'gross_salary' => $this->gross_salary,
            'total_earnings' => $this->total_earnings,
            'total_deductions' => $this->total_deductions,
            'net_salary' => $this->net_salary,
            'status' => $this->status,
            'payment_date' => $this->payment_date?->format('Y-m-d'),
            
            'employee' => new EmployeeResource($this->whenLoaded('employee')),
            'pay_period' => new PayPeriodResource($this->whenLoaded('payPeriod')),
            
            'lines' => PayslipLineResource::collection($this->whenLoaded('lines')),
            
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
        ];
    }
}