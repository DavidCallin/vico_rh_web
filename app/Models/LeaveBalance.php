<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaveBalance extends Model
{
    protected $fillable = [
        'employee_id',
        'year',
        'total_days',
        'taken_days',
        'remaining_days',
        'pending_days',
    ];

    protected $casts = [
        'year' => 'integer',
        'total_days' => 'decimal:2',
        'taken_days' => 'decimal:2',
        'remaining_days' => 'decimal:2',
        'pending_days' => 'decimal:2',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Recalcule automatiquement les jours restants
     */
    public function recalculateRemaining(): void
    {
        $this->remaining_days = $this->total_days - $this->taken_days - $this->pending_days;
        $this->save();
    }
}