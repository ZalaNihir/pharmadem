<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\PermissionRegistrar;

class EmployeeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $employees = [
            ['name' => 'Sample Employee One', 'email' => 'employee1@example.com'],
            ['name' => 'Sample Employee Two', 'email' => 'employee2@example.com'],
            ['name' => 'Sample Employee Three', 'email' => 'employee3@example.com'],
        ];

        foreach ($employees as $employeeDetails) {
            $employee = User::query()->where('email', $employeeDetails['email'])->first();

            if (! $employee) {
                $employee = User::create([
                    ...$employeeDetails,
                    'password' => Hash::make('Employee@12345'),
                ]);
            }

            if (! $employee->hasAnyRole(['Admin', 'Manager']) && ! $employee->hasRole('Employee')) {
                $employee->assignRole('Employee');
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
