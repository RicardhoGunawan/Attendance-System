<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Buat role admin dan employee
        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $employeeRole = Role::firstOrCreate(['name' => 'employee', 'guard_name' => 'web']);

        // Generate permissions untuk resource yang ada
        $resources = [
            'UserResource',
            'OfficeResource',
            'ScheduleResource',
            'ShiftResource',
            'AttendanceResource',
            'LeaveRequestResource',
        ];

        foreach ($resources as $resource) {
            $permissions = [
                "view_{$resource}",
                "view_any_{$resource}",
                "create_{$resource}",
                "update_{$resource}",
                "delete_{$resource}",
                "delete_any_{$resource}",
            ];

            foreach ($permissions as $permission) {
                Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
            }
        }

        // Custom permissions
        $customPermissions = [
            'view_attendance',
            'create_leave_request',
            'manage_users',
            'manage_offices',
            'manage_schedules',
            'manage_shifts',
            'manage_attendance',
            'manage_leave_requests',
        ];

        foreach ($customPermissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        // Assign all permissions to admin
        $adminRole->givePermissionTo(Permission::all());

        // Assign specific permissions to employee
        $employeeRole->givePermissionTo([
            'view_attendance',
            'create_leave_request',
            'view_AttendanceResource',
            'view_any_AttendanceResource',
            'view_LeaveRequestResource',
            'view_any_LeaveRequestResource',
            'create_LeaveRequestResource',
            'update_LeaveRequestResource',
        ]);

        // Create admin user if not exists
        User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Admin User',
                'password' => bcrypt('password'),
            ]
        )->assignRole($adminRole);

        // Create employee user if not exists
        User::firstOrCreate(
            ['email' => 'employee@example.com'],
            [
                'name' => 'Employee User',
                'password' => bcrypt('password'),
            ]
        )->assignRole($employeeRole);
    }
}