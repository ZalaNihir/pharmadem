<?php

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Facades\Hash;

test('database seeding creates role-specific development accounts idempotently', function () {
    $this->seed(DatabaseSeeder::class);

    $admin = User::where('email', 'admin@example.com')->firstOrFail();
    $adminPermissions = $admin->getAllPermissions()->pluck('name')->all();
    expect($admin->hasRole('Admin'))->toBeTrue();
    expect(Hash::check('Admin@12345', $admin->password))->toBeTrue();
    expect($adminPermissions)->toContain('manage users', 'manage roles', 'manage all tasks', 'manage team tasks', 'submit work updates', 'review team updates', 'view reports', 'evaluate employees');

    foreach (['employee1@example.com', 'employee2@example.com', 'employee3@example.com'] as $email) {
        $employee = User::where('email', $email)->firstOrFail();
        expect($employee->getRoleNames()->all())->toBe(['Employee']);
        expect($employee->hasAnyRole(['Admin', 'Manager']))->toBeFalse();
        expect(Hash::check('Employee@12345', $employee->password))->toBeTrue();
    }

    $admin->forceFill(['name' => 'Renamed Administrator', 'password' => Hash::make('Changed@54321')])->save();
    $employee = User::where('email', 'employee1@example.com')->firstOrFail();
    $employee->forceFill(['name' => 'Renamed Employee', 'password' => Hash::make('Changed@54321')])->save();
    $this->seed(DatabaseSeeder::class);

    expect(User::where('email', 'admin@example.com')->count())->toBe(1);
    expect(User::where('email', 'employee1@example.com')->count())->toBe(1);
    expect($admin->fresh()->name)->toBe('Renamed Administrator');
    expect(Hash::check('Changed@54321', $admin->fresh()->password))->toBeTrue();
    expect($employee->fresh()->name)->toBe('Renamed Employee');
    expect(Hash::check('Changed@54321', $employee->fresh()->password))->toBeTrue();
});
