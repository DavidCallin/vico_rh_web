<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class PayslipTemplate extends Model
{
    protected $fillable = [
        'name',
        'content',
        'is_default',
        'category_id',
    ];

    protected $casts = [
        'is_default' => 'boolean',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    // Relation avec les rubriques via la table pivot
    public function payrollItems(): BelongsToMany
    {
        return $this->belongsToMany(
            PayrollItem::class, 
            'payslip_template_items'  // 
        )
            ->withPivot('isActive', 'displayOrder')
            ->withTimestamps();
    }
}