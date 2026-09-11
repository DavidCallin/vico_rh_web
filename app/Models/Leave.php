<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Leave extends Model
{
    use HasFactory;
    protected $fillable = [
        'leave_type', 'start_date', 'end_date', 'days_count',
        'status', 'validation_date', 'reason',
        'employee_id', 'validator_id', 'pay_period_id'
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'validation_date' => 'date',
    ];

    // L'employé qui a demandé le congé
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    // L'employé qui a validé (nullable)
    public function validator(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'validator_id');
    }

    // La période de paie associée (nullable)
    public function payPeriod(): BelongsTo
    {
        return $this->belongsTo(PayPeriod::class);
    }

    // Historique des mouvements liés à ce congé
    public function balanceHistories(): HasMany
    {
        return $this->hasMany(LeaveBalanceHistory::class);
    }

    // Scope pour les congés en attente
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    // Scope pour les congés approuvés
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }
}