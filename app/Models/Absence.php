<?php

namespace App\Models;

use Database\Factories\AbsenceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Absence extends Model
{
    /** @use HasFactory<AbsenceFactory> */
    use HasFactory;

    protected $fillable = [
        'type', 'start_date', 'end_date', 'reason',
        'proof_path', 'status', 'validation_date',
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
        return $this->belongsTo(PayPeriod::class, 'pay_period_id');
    }
}
