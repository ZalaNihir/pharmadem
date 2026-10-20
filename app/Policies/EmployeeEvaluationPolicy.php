<?php

namespace App\Policies;

use App\Models\EmployeeEvaluation;
use App\Models\User;

class EmployeeEvaluationPolicy
{
    public function before(User $user): ?bool
    {
        return $user->hasRole('Admin') ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['Manager', 'Employee']);
    }

    public function view(User $user, EmployeeEvaluation $evaluation): bool
    {
        return $user->hasRole('Manager') ? $evaluation->employee?->manager_id === $user->id : $evaluation->employee_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->hasRole('Manager');
    }

    public function update(User $user, EmployeeEvaluation $evaluation): bool
    {
        return $user->hasRole('Manager') && $evaluation->manager_id === $user->id;
    }

    public function delete(User $user, EmployeeEvaluation $evaluation): bool
    {
        return $user->hasRole('Manager') && $evaluation->manager_id === $user->id;
    }
}
