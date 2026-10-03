<?php

namespace App\Models;

use Database\Factories\PayrollItemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PayrollItem extends Model
{
    /** @use HasFactory<PayrollItemFactory> */
    use HasFactory;

    protected $fillable = [
        'name', 
        'code', 
        'type', 
        'taxStatus',
        'amount_or_rate', 
        'defaultValue',    
        'calculation', 
        'isActive', 
        'displayOrder',
    ];

    protected $casts = [
        'amount_or_rate' => 'decimal:4',
        'defaultValue' => 'decimal:4',  
        'isActive' => 'boolean',
        'displayOrder' => 'integer',
    ];

    public function payslipLines(): HasMany
    {
        return $this->hasMany(PayslipLine::class, 'payroll_item_id');
    }
}