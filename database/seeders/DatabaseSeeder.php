<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Buat role "admin" jika belum ada
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        
        // Buat role "employee" jika belum ada
        $employeeRole = Role::firstOrCreate(['name' => 'employee']);

        // Buat user admin
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'), // Ganti dengan password yang lebih aman
        ]);
        $admin->assignRole($adminRole);

        // Buat user employee
        $employee = User::create([
            'name' => 'Employee User',
            'email' => 'employee@example.com',
            'password' => bcrypt('password'), // Ganti dengan password yang lebih aman
        ]);
        $employee->assignRole($employeeRole);
    }
}
