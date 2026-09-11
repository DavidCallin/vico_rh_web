<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Department;
use App\Models\Position;
use App\Models\Employee;
use App\Models\User;
use App\Models\PayrollItem;
use App\Models\Contract;
use App\Models\Leave;
use Illuminate\Database\Seeder;

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
            'department_id' => fn() => $departments->random()->id,
            'category_id' => fn() => $categories->random()->id,
        ]);
        $this->command->info('✅ 15 postes créés');

        // 4. Employés
        $employees = Employee::factory(30)->create([
            'position_id' => fn() => $positions->random()->id,
            'category_id' => fn() => $categories->random()->id,
        ]);
        $this->command->info('✅ 30 employés créés');

        // 5. Managers
        $departments->take(6)->each(function ($dept, $index) use ($employees) {
            $dept->update(['manager_id' => $employees[$index]->id]);
        });
        $this->command->info('✅ Managers assignés aux départements');

        // 6. Utilisateurs (10 employés uniques)
        $employeesForUsers = $employees->random(10);
        foreach ($employeesForUsers as $employee) {
            User::factory()->create([
                'employee_id' => $employee->id,
                'name' => $employee->first_name . ' ' . $employee->last_name,
                'email' => $employee->email,
                'login' => strtolower($employee->first_name) . '.' . strtolower($employee->last_name),
            ]);
        }
        $this->command->info('✅ 10 utilisateurs créés');

        // 7. Périodes de paie (SÉQUENTIELLES - PAS DE FACTORY ICI)
        for ($month = 1; $month <= 12; $month++) {
            \App\Models\PayPeriod::create([
                'month' => $month,
                'year' => 2024,
                'status' => $month < 9 ? 'closed' : 'open', 
                'opening_date' => \Carbon\Carbon::create(2024, $month, 1),
                'closing_date' => $month < 9 ? \Carbon\Carbon::create(2024, $month, 25) : null,
                'closure_type' => $month < 9 ? 'normal' : null,
                'closed_by' => null,
            ]);
        }
        $this->command->info('✅ 12 périodes de paie créées (année 2024)');

        // 8. Rubriques de paie
        $payrollItems = PayrollItem::factory(10)->create();
        $this->command->info('✅ 10 rubriques de paie créées');

        // 9. Contrats (1 par employé)
        $employees->each(function ($employee) {
            Contract::factory()->create(['employee_id' => $employee->id]);
        });
        $this->command->info('✅ 30 contrats créés');

        // 10. Congés (50 au total, répartis aléatoirement)
        Leave::factory(50)->create([
            'employee_id' => fn() => $employees->random()->id,
        ]);
        $this->command->info('✅ 50 congés créés');

        $this->command->info('🎉 Base de données peuplée avec succès !');
    }
}