<?php

namespace App\Models;

use Database\Factories\EmployeeFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property-read string $full_name
 * @property-read float $seniority
 */
class Employee extends Model
{
    /** @use HasFactory<EmployeeFactory> */
    use HasFactory;

    use SoftDeletes;

    protected $fillable = [
        'matricule', 'first_name', 'last_name', 'birth_date',
        'gender', 'phone', 'email', 'address', 'hire_date',
        'site', 'status', 'position_id', 'category_id',
    ];

    protected $casts = [
        'birth_date' => 'date',
        'hire_date' => 'date',
    ];

    // ===== RELATIONS BELONGS TO (FK dans employees) =====

    /**
     * @return BelongsTo<Position, $this>
     */
    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class);
    }

    /**
     * @return BelongsTo<Category, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * @return HasOneThrough<Department, Position, $this>
     */
    public function department(): HasOneThrough
    {
        return $this->hasOneThrough(
            Department::class,
            Position::class,
            'id',
            'id',
            'position_id',
            'department_id',
        );
    }

    // ===== RELATIONS HAS MANY (FK dans les autres tables) =====

    /**
     * @return HasMany<Document, $this>
     */
    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    /**
     * @return HasMany<Contract, $this>
     */
    public function contracts(): HasMany
    {
        return $this->hasMany(Contract::class);
    }

    /**
     * @return HasMany<Leave, $this>
     */
    public function leaves(): HasMany
    {
        return $this->hasMany(Leave::class);
    }

    /**
     * @return HasMany<Absence, $this>
     */
    public function absences(): HasMany
    {
        return $this->hasMany(Absence::class);
    }

    /**
     * @return HasMany<LeaveBalanceHistory, $this>
     */
    public function leaveBalanceHistories(): HasMany
    {
        return $this->hasMany(LeaveBalanceHistory::class);
    }

    /**
     * @return HasMany<Payslip, $this>
     */
    public function payslips(): HasMany
    {
        return $this->hasMany(Payslip::class);
    }

    // ===== RELATIONS RÉFLEXIVES (Employee → Employee) =====

    /**
     * @return HasMany<Leave, $this>
     */
    public function validatedLeaves(): HasMany
    {
        return $this->hasMany(Leave::class, 'validator_id');
    }

    /**
     * @return HasMany<Absence, $this>
     */
    public function validatedAbsences(): HasMany
    {
        return $this->hasMany(Absence::class, 'validator_id');
    }

    // ===== RELATION AVEC USER (1 employé = 0 ou 1 user) =====

    /**
     * @return HasOne<User, $this>
     */
    public function user(): HasOne
    {
        return $this->hasOne(User::class);
    }

    // ===== RELATION AVEC DEPARTMENT (responsable) =====

    /**
     * @return HasMany<Department, $this>
     */
    public function managedDepartments(): HasMany
    {
        return $this->hasMany(Department::class, 'manager_id');
    }

    // ===== SCOPES (Filtres réutilisables) =====

    /**
     * @param  Builder<Employee>  $query
     * @return Builder<Employee>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    /**
     * @param  Builder<Employee>  $query
     * @return Builder<Employee>
     */
    public function scopeInTrial(Builder $query): Builder
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
