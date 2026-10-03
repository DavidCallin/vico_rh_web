<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Payslip extends Model
{
    use HasFactory;
    protected $fillable = [
        'generation_date', 'gross_salary', 'total_earnings',
        'total_deductions', 'net_salary', 'file_path', 'status', 'payment_date',
        'employee_id', 'pay_period_id'
    ];

    protected $casts = [
        'generation_date' => 'date',
        'payment_date' => 'date',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function payPeriod(): BelongsTo
    {
        return $this->belongsTo(PayPeriod::class);
    }

    // Les lignes du bulletin (salaire de base, CNSS, IR, etc.)
    public function lines(): HasMany
    {
        return $this->hasMany(PayslipLine::class);
    }

    // Scope pour les bulletins validés
    public function scopeValidated($query)
    {
        return $query->where('status', 'validated');
    }
}
