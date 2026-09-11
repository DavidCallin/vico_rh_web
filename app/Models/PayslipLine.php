<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayslipLine extends Model
{

    use HasFactory;
    
    protected $fillable = ['amount', 'quantity', 'payslip_id', 'payroll_item_id'];

    public function payslip(): BelongsTo
    {
        return $this->belongsTo(Payslip::class);
    }

    // La rubrique de paie (ex: Salaire de base, CNSS, IR)
    public function payrollItem(): BelongsTo
    {
        return $this->belongsTo(PayrollItem::class);
    }
}