<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\SoftDeletes;

class Department extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = ['name', 'description', 'code', 'manager_id'];

    // Un département a plusieurs postes
    public function positions(): HasMany
    {
        return $this->hasMany(Position::class);
    }

    //Un département à un responsable
    public function manager(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'manager_id');
    }

    //Un département peut avoir plusieurs rôle (table pivot)
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(
            \Spatie\Permission\Models\Role::class, 
            'role_department'
        );
    }

    /**
     * Relation avec les employés 
     */
    public function employees(): HasManyThrough
    {
        return $this->hasManyThrough(
            Employee::class,    // Modèle final cible
            Position::class,    // Modèle intermédiaire
            'department_id',    // Clé étrangère dans positions (lie au département)
            'position_id',      // Clé étrangère dans employees (lie au poste)
            'id',               // Clé primaire locale dans departments
            'id'                // Clé primaire locale dans positions
        );
    }

}
