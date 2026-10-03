<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PayslipLineResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'amount' => $this->amount,
            'quantity' => $this->quantity,
            'payroll_item' => [
                'id' => $this->payrollItem?->id,
                'name' => $this->payrollItem?->name,
                'code' => $this->payrollItem?->code,
                'type' => $this->payrollItem?->type,
            ],
        ];
    }
}