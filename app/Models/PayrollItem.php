<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PayrollItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'code', 'type', 'amount_or_rate',
        'calculation', 'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'amount_or_rate' => 'decimal:2',
    ];

    public function payslipLines(): HasMany
    {
        return $this->hasMany(PayslipLine::class);
    }
}
