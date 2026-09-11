<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Employee extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'matricule', 'first_name', 'last_name', 'birth_date', 
        'gender', 'phone', 'email', 'address', 'hire_date', 
        'site', 'status', 'position_id', 'category_id'
    ];

    protected $casts = [
        'birth_date' => 'date',
        'hire_date' => 'date',
    ];

    // ===== RELATIONS BELONGS TO (FK dans employees) =====

    // Un employé occupe un poste
    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class);
    }

    // Un employé appartient à une catégorie
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    // Accès rapide au département via le poste
    public function department()
    {
        return $this->position->department;
    }

    // ===== RELATIONS HAS MANY (FK dans les autres tables) =====

    // Un employé a plusieurs documents
    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    // Un employé a plusieurs contrats
    public function contracts(): HasMany
    {
        return $this->hasMany(Contract::class);
    }

    // Un employé a plusieurs congés (demandés)
    public function leaves(): HasMany
    {
        return $this->hasMany(Leave::class);
    }

    // Un employé a plusieurs absences
    public function absences(): HasMany
    {
        return $this->hasMany(Absence::class);
    }

    // Un employé a un historique de solde de congés
    public function leaveBalanceHistories(): HasMany
    {
        return $this->hasMany(LeaveBalanceHistory::class);
    }

    // Un employé a plusieurs bulletins de paie
    public function payslips(): HasMany
    {
        return $this->hasMany(Payslip::class);
    }

    // ===== RELATIONS RÉFLEXIVES (Employee → Employee) =====

    // Un employé peut valider des congés (FK validator_id dans leaves)
    public function validatedLeaves(): HasMany
    {
        return $this->hasMany(Leave::class, 'validator_id');
    }

    // Un employé peut valider des absences (FK validator_id dans absences)
    public function validatedAbsences(): HasMany
    {
        return $this->hasMany(Absence::class, 'validator_id');
    }

    // ===== RELATION AVEC USER (1 employé = 0 ou 1 user) =====

    // Un employé peut avoir un compte utilisateur
    public function user(): HasOne
    {
        return $this->hasOne(User::class);
    }

    // ===== RELATION AVEC DEPARTMENT (responsable) =====

    // Un employé peut être responsable d'un département
    public function managedDepartments(): HasMany
    {
        return $this->hasMany(Department::class, 'manager_id');
    }

    // ===== SCOPES (Filtres réutilisables) =====

    // Scope pour récupérer uniquement les employés actifs
    public function scopeActive($query)
    {
        return $query->where('status', 'actif');
    }

    // Scope pour récupérer les employés en période d'essai
    public function scopeInTrial($query)
    {
        return $query->where('status', 'trial');
    }

    // ===== ATTRIBUTS CALCULÉS (Accessors) =====

    // Nom complet de l'employé
    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    // Ancienneté en années
    public function getSeniorityAttribute(): float
    {
        return round($this->hire_date->diffInYears(now(), true), 1);
    }
}