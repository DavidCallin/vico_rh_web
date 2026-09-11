<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PayrollItem extends Model
{
    protected $fillable = [
        'name', 'code', 'type', 'amount_or_rate', 
        'calculation', 'is_active'
    ];

    // Une rubrique apparaît dans plusieurs lignes de bulletin
    public function paysLipLines(): HasMany
    {
        return $this->hasMany(PayslipLine::class);
    }
}
