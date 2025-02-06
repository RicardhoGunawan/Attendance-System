<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleAndPermissionSeeder extends Seeder
{
    public function run()
    {
        // Create roles
        $adminRole = Role::create(['name' => 'admin']);
        $employeeRole = Role::create(['name' => 'employee']);

        // Create permissions
        $permissions = [
            'manage users',
            'manage offices',
            'manage schedules',
            'manage shifts',
            'manage attendance',
            'manage leave requests',
            'view attendance',
            'create leave request',
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }

        // Assign permissions to roles
        $adminRole->givePermissionTo(Permission::all());
        $employeeRole->givePermissionTo([
            'view attendance',
            'create leave request',
        ]);
    }
}
