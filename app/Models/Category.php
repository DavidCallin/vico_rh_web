<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'code'];

    // Une catégorie à plusieurs postes
    public function positions(): HasMany
    {
        return $this->hasMany(Position::class);
    }

    // Une catégorie à plusieurs employés
    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }
}
