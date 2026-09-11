<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PayPeriod extends Model
{
    protected $fillable = [
        'month', 'year', 'status', 'opening_date',
        'closing_date', 'closure_type', 'closed_by'
    ];

    protected $casts = [
        'opening_date' => 'date',
        'closing_date' => 'date',
    ];

    // L'utilisateur qui a clôturé la période
    public function closedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function leaves(): HasMany
    {
        return $this->hasMany(Leave::class);
    }

    public function absences(): HasMany
    {
        return $this->hasMany(Absence::class);
    }

    public function leaveBalanceHistories(): HasMany
    {
        return $this->hasMany(LeaveBalanceHistory::class);
    }

    public function payslips(): HasMany
    {
        return $this->hasMany(Payslip::class);
    }

    // Scope pour les périodes ouvertes
    public function scopeOpen($query)
    {
        return $query->where('status', 'open');
    }
}