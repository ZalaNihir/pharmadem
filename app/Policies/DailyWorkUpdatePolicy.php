<?php

namespace App\Policies;

use App\Models\DailyWorkUpdate;
use App\Models\User;

class DailyWorkUpdatePolicy
{
    public function before(User $user): ?bool
    {
        return $user->hasRole('Admin') ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['Manager', 'Employee']);
    }

    public function view(User $user, DailyWorkUpdate $update): bool
    {
        return $user->hasRole('Manager') ? $update->employee?->manager_id === $user->id : $update->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['Manager', 'Employee']);
    }

    public function update(User $user, DailyWorkUpdate $update): bool
    {
        return $update->user_id === $user->id;
    }

    public function delete(User $user, DailyWorkUpdate $update): bool
    {
        return $update->user_id === $user->id;
    }
}
