<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Réinitialiser le cache des rôles et permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // ===== CRÉATION DES PERMISSIONS =====
        
        // Permissions Employés
        Permission::create(['name' => 'employees.view']);
        Permission::create(['name' => 'employees.create']);
        Permission::create(['name' => 'employees.update']);
        Permission::create(['name' => 'employees.delete']);
        
        // Permissions Congés
        Permission::create(['name' => 'leaves.view']);
        Permission::create(['name' => 'leaves.create']);
        Permission::create(['name' => 'leaves.approve']);
        Permission::create(['name' => 'leaves.reject']);
        
        // Permissions Absences
        Permission::create(['name' => 'absences.view']);
        Permission::create(['name' => 'absences.create']);
        Permission::create(['name' => 'absences.approve']);
        
        // Permissions Contrats
        Permission::create(['name' => 'contracts.view']);
        Permission::create(['name' => 'contracts.create']);
        Permission::create(['name' => 'contracts.update']);
        
        // Permissions Paie
        Permission::create(['name' => 'payroll.view']);
        Permission::create(['name' => 'payroll.manage']);
        
        // Permissions Administration
        Permission::create(['name' => 'users.manage']);
        Permission::create(['name' => 'roles.manage']);
        Permission::create(['name' => 'departments.manage']);

        // ===== CRÉATION DES RÔLES =====
        
        // Admin : TOUT gérer
        $admin = Role::create(['name' => 'admin']);
        $admin->givePermissionTo(Permission::all());
        
        // RH : Gérer les employés, congés, absences, contrats
        $rh = Role::create(['name' => 'rh']);
        $rh->givePermissionTo([
            'employees.view', 'employees.create', 'employees.update', 'employees.delete',
            'leaves.view', 'leaves.create', 'leaves.approve', 'leaves.reject',
            'absences.view', 'absences.create', 'absences.approve',
            'contracts.view', 'contracts.create', 'contracts.update',
            'payroll.view',
        ]);
        
        // Manager : Voir les employés de son département, approuver congés/absences
        $manager = Role::create(['name' => 'manager']);
        $manager->givePermissionTo([
            'employees.view',
            'leaves.view', 'leaves.approve', 'leaves.reject',
            'absences.view', 'absences.approve',
            'contracts.view',
        ]);
        
        // Employee : Voir ses propres infos, demander des congés
        $employee = Role::create(['name' => 'employee']);
        $employee->givePermissionTo([
            'leaves.view', 'leaves.create',
            'absences.view', 'absences.create',
        ]);

        $this->command->info('✅ Rôles et permissions créés avec succès !');
    }
}