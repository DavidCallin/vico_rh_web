<?php

namespace App\Models;

use Database\Factories\PayPeriodFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PayPeriod extends Model
{
    /** @use HasFactory<PayPeriodFactory> */
    use HasFactory;

    protected $fillable = [
        'month', 'year', 'status', 'opening_date',
        'closing_date', 'closure_type', 'closed_by',
    ];

    protected $casts = [
        'opening_date' => 'date',
        'closing_date' => 'date',
    ];

    /**
     * @return BelongsTo<User, $this>
     */
    public function closedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    /**
     * @return HasMany<Leave, $this>
     */
    public function leaves(): HasMany
    {
        return $this->hasMany(Leave::class);
    }

    /**
     * @return HasMany<Absence, $this>
     */
    public function absences(): HasMany
    {
        return $this->hasMany(Absence::class);
    }

    /**
     * @return HasMany<LeaveBalanceHistory, $this>
     */
    public function leaveBalanceHistories(): HasMany
    {
        return $this->hasMany(LeaveBalanceHistory::class);
    }

    /**
     * @return HasMany<Payslip, $this>
     */
    public function payslips(): HasMany
    {
        return $this->hasMany(Payslip::class);
    }

    /**
     * @param  Builder<PayPeriod>  $query
     * @return Builder<PayPeriod>
     */
    public function scopeOpen(Builder $query): Builder
    {
        return $query->where('status', 'open');
    }
}
