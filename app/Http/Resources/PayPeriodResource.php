<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PayPeriodResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'month' => $this->month,
            'year' => $this->year,
            'label' => \Carbon\Carbon::create($this->year, $this->month, 1)->translatedFormat('F Y'),
            'status' => $this->status,
            'opening_date' => $this->opening_date?->format('Y-m-d'),
            'closing_date' => $this->closing_date?->format('Y-m-d'),
            'closure_type' => $this->closure_type,
            'closed_by' => $this->whenLoaded('closedByUser', function () {
                return [
                    'id' => $this->closedByUser?->id,
                    'name' => $this->closedByUser?->name,
                ];
            }),
            'payslips_count' => $this->whenLoaded('payslips', fn() => $this->payslips->count()),
            
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
        ];
    }
}