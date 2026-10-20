<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = ['manage users', 'manage roles', 'manage all tasks', 'manage team tasks', 'submit work updates', 'review team updates', 'view reports', 'evaluate employees'];
        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $admin = Role::findOrCreate('Admin', 'web');
        $manager = Role::findOrCreate('Manager', 'web');
        $employee = Role::findOrCreate('Employee', 'web');
        $admin->syncPermissions($permissions);
        $manager->syncPermissions(['manage team tasks', 'submit work updates', 'review team updates', 'view reports', 'evaluate employees']);
        $employee->syncPermissions(['submit work updates']);

        User::query()->whereDoesntHave('roles')->each(function (User $user): void {
            $user->assignRole('Employee');
        });

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
