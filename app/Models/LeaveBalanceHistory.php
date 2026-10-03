<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaveBalanceHistory extends Model
{
    /** @use HasFactory<Factory<LeaveBalanceHistory>> */
    use HasFactory;

    protected $fillable = [
        'date', 'movement_type', 'days_count', 'balance_after',
        'reason', 'employee_id', 'leave_id', 'pay_period_id',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    /**
     * @return BelongsTo<Employee, $this>
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * @return BelongsTo<Leave, $this>
     */
    public function leave(): BelongsTo
    {
        return $this->belongsTo(Leave::class);
    }

    /**
     * @return BelongsTo<PayPeriod, $this>
     */
    public function payPeriod(): BelongsTo
    {
        return $this->belongsTo(PayPeriod::class);
    }
}
