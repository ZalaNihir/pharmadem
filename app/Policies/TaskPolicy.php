<?php

namespace App\Policies;

use App\Models\Task;
use App\Models\User;

class TaskPolicy
{
    public function before(User $user): ?bool
    {
        return $user->hasRole('Admin') ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['Manager', 'Employee']);
    }

    public function view(User $user, Task $task): bool
    {
        return $user->hasRole('Manager') ? $task->employee?->manager_id === $user->id : $task->assigned_to === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->hasRole('Manager');
    }

    public function update(User $user, Task $task): bool
    {
        return $user->hasRole('Manager') ? $task->employee?->manager_id === $user->id : $task->assigned_to === $user->id;
    }

    public function delete(User $user, Task $task): bool
    {
        return $user->hasRole('Manager') && $task->employee?->manager_id === $user->id;
    }
}
