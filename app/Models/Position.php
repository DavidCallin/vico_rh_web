<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Position extends Model
{
    use HasFactory;

    protected $fillable = ['title', 'department_id', 'category_id'];

    /**
     * @return BelongsTo<Department, $this>
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    // Un poste appartient à une catégorie
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    // Un poste a plusieurs employés
    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }
}
