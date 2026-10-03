<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Contract;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Leave;
use App\Models\PayPeriod;
use App\Models\PayrollItem;
use App\Models\Position;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Catégories
        $categories = Category::factory(5)->create();
        $this->command->info('✅ 5 catégories créées');

        // 2. Départements
        $departments = Department::factory(6)->create();
        $this->command->info('✅ 6 départements créés');

        // 3. Postes
        $positions = Position::factory(15)->create([
            'department_id' => fn () => $departments->random()->id,
            'category_id' => fn () => $categories->random()->id,
        ]);
        $this->command->info('✅ 15 postes créés');

        // 4. Employés
        $employees = Employee::factory(30)->create([
            'position_id' => fn () => $positions->random()->id,
            'category_id' => fn () => $categories->random()->id,
        ]);
        $this->command->info('✅ 30 employés créés');

        // 5. Managers
        $departments->take(6)->each(function ($dept, $index) use ($employees) {
            $dept->update(['manager_id' => $employees[$index]->id]);
        });
        $this->command->info('✅ Managers assignés aux départements');

        // 6. Utilisateurs (10 employés uniques)
        $employeesForUsers = $employees->random(10);
        $users = collect(); // ← Initialiser une collection vide
        
        foreach ($employeesForUsers as $employee) {
            $user = User::factory()->create([
                'employee_id' => $employee->id,
                'name' => $employee->first_name.' '.$employee->last_name,
                'email' => $employee->email,
                'login' => Str::slug($employee->first_name.' '.$employee->last_name).'.'.$employee->id,
            ]);
            $users->push($user); // ← Ajouter l'utilisateur à la collection
        }
        $this->command->info('✅ 10 utilisateurs créés');

        // 7. Périodes de paie
        for ($month = 1; $month <= 12; $month++) {
            PayPeriod::create([
                'month' => $month,
                'year' => 2024,
                'status' => $month < 9 ? 'closed' : 'open',
                'opening_date' => Carbon::create(2024, $month, 1),
                'closing_date' => $month < 9 ? Carbon::create(2024, $month, 25) : null,
                'closure_type' => $month < 9 ? 'normal' : null,
                'closed_by' => null,
            ]);
        }
        $this->command->info('✅ 12 périodes de paie créées (année 2024)');

        // 8. Rubriques de paie
        $payrollItems = PayrollItem::factory(10)->create();
        $this->command->info('✅ 10 rubriques de paie créées');

        // 9. Contrats
        $employees->each(function ($employee) {
            Contract::factory()->create(['employee_id' => $employee->id]);
        });
        $this->command->info('✅ 30 contrats créés');

        // 10. Congés
        Leave::factory(50)->create([
            'employee_id' => fn () => $employees->random()->id,
        ]);
        $this->command->info('✅ 50 congés créés');

        // 11. Rôles et Permissions (via le seeder dédié)
        $this->call(RolesAndPermissionsSeeder::class);
        $this->command->info('✅ Rôles et permissions créés');

        // 12. Assigner des rôles aux utilisateurs
        $users[0]->assignRole('admin');
        $users->slice(1, 3)->each->assignRole('rh');
        $users->slice(4, 6)->each->assignRole('manager');
        $users->slice(7)->each->assignRole('employee');
        $this->command->info('✅ Rôles assignés aux utilisateurs');

        // 13. Settings par défaut
        $this->call(SettingsSeeder::class);
        $this->command->info('✅ Settings par défaut créés');

        $this->command->info('🎉 Base de données peuplée avec succès !');
    }

    private function seedDemo(): void
    {
        $role = Role::findOrCreate('rh', 'web');
        $category = Category::factory()->create([
            'name' => 'Cadre',
            'code' => 'DEMORH',
        ]);
        $department = Department::factory()->create([
            'name' => 'Direction',
            'description' => 'Direction générale',
        ]);
        $hrPosition = Position::factory()->create([
            'title' => 'Responsable des ressources humaines',
            'department_id' => $department->id,
            'category_id' => $category->id,
        ]);
        $staffPosition = Position::factory()->create([
            'title' => 'Assistant administratif',
            'department_id' => $department->id,
            'category_id' => $category->id,
        ]);

        $hrEmployee = Employee::factory()->create([
            'matricule' => 'EMP-RH-001',
            'first_name' => 'Aline',
            'last_name' => 'Dossou',
            'email' => 'rh@paie.bj',
            'gender' => 'F',
            'status' => 'active',
            'hire_date' => '2020-01-06',
            'site' => 'Cotonou',
            'position_id' => $hrPosition->id,
            'category_id' => $category->id,
        ]);
        $employee = Employee::factory()->create([
            'matricule' => 'EMP-SAL-001',
            'first_name' => 'Kodjo',
            'last_name' => 'Assogba',
            'email' => 'employe@paie.bj',
            'gender' => 'M',
            'status' => 'active',
            'hire_date' => '2024-03-01',
            'site' => 'Cotonou',
            'position_id' => $staffPosition->id,
            'category_id' => $category->id,
        ]);
        $department->update(['manager_id' => $hrEmployee->id]);

        User::factory()->create([
            'name' => 'Aline Dossou',
            'email' => 'rh@paie.bj',
            'login' => 'rh',
            'employee_id' => $hrEmployee->id,
        ])->assignRole($role);

        User::factory()->create([
            'name' => 'Kodjo Assogba',
            'email' => 'employe@paie.bj',
            'login' => 'employe',
            'employee_id' => $employee->id,
        ]);

        Contract::factory()->create([
            'employee_id' => $hrEmployee->id,
            'contract_type' => 'cdi',
            'start_date' => '2020-01-06',
            'end_date' => null,
            'base_salary' => 500000,
        ]);
        Contract::factory()->create([
            'employee_id' => $employee->id,
            'contract_type' => 'cdd',
            'start_date' => now()->startOfMonth()->subMonths(2)->toDateString(),
            'end_date' => now()->endOfMonth()->toDateString(),
            'base_salary' => 350000,
        ]);
        Contract::factory()->create([
            'employee_id' => $employee->id,
            'contract_type' => 'cdd',
            'start_date' => now()->subYear()->startOfMonth()->toDateString(),
            'end_date' => now()->subMonth()->endOfMonth()->toDateString(),
            'base_salary' => 280000,
        ]);

        PayPeriod::query()->firstOrCreate(
            ['month' => now()->month, 'year' => now()->year],
            [
                'status' => 'open',
                'opening_date' => now()->startOfMonth()->toDateString(),
            ],
        );

        $this->command->info('✅ Comptes de démonstration : rh@paie.bj et employe@paie.bj (mot de passe : password)');
    }
}
