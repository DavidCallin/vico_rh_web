<?php

namespace App\Models;

use Database\Factories\LeaveFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Leave extends Model
{
    /** @use HasFactory<LeaveFactory> */
    use HasFactory;

    protected $fillable = [
        'leave_type', 'start_date', 'end_date', 'days_count',
        'status', 'validation_date', 'reason',
        'employee_id', 'validator_id', 'pay_period_id',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'validation_date' => 'date',
    ];

    /**
     * @return BelongsTo<Employee, $this>
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * @return BelongsTo<Employee, $this>
     */
    public function validator(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'validator_id');
    }

    /**
     * @return BelongsTo<PayPeriod, $this>
     */
    public function payPeriod(): BelongsTo
    {
        return $this->belongsTo(PayPeriod::class);
    }

    /**
     * @return HasMany<LeaveBalanceHistory, $this>
     */
    public function balanceHistories(): HasMany
    {
        return $this->hasMany(LeaveBalanceHistory::class);
    }

    /**
     * @param  Builder<Leave>  $query
     * @return Builder<Leave>
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }

    /**
     * @param  Builder<Leave>  $query
     * @return Builder<Leave>
     */
    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', 'approved');
    }
}
