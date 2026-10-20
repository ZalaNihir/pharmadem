<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\PermissionRegistrar;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $admin = User::query()->where('email', 'admin@example.com')->first();

        if (! $admin) {
            $admin = User::create([
                'name' => 'System Administrator',
                'email' => 'admin@example.com',
                'password' => Hash::make('Admin@12345'),
            ]);
        }

        if (! $admin->hasRole('Admin')) {
            $admin->assignRole('Admin');
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
