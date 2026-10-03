<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Payslip extends Model
{
    /** @use HasFactory<Factory<Payslip>> */
    use HasFactory;

    protected $fillable = [
        'generation_date', 'gross_salary', 'total_earnings',
        'total_deductions', 'net_salary', 'file_path', 'status', 'payment_date',
        'employee_id', 'pay_period_id'
    ];

    protected $casts = [
        'generation_date' => 'date',
        'payment_date' => 'date',
        'delivered_at' => 'datetime',
        'gross_salary' => 'decimal:2',
        'total_earnings' => 'decimal:2',
        'total_deductions' => 'decimal:2',
        'net_salary' => 'decimal:2',
    ];

    /**
     * @return BelongsTo<Employee, $this>
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * @return BelongsTo<PayPeriod, $this>
     */
    public function payPeriod(): BelongsTo
    {
        return $this->belongsTo(PayPeriod::class);
    }

    /**
     * @return HasMany<PayslipLine, $this>
     */
    public function lines(): HasMany
    {
        return $this->hasMany(PayslipLine::class);
    }

    /**
     * @param  Builder<Payslip>  $query
     * @return Builder<Payslip>
     */
    public function scopeValidated(Builder $query): Builder
    {
        return $query->where('status', 'validated');
    }
}
