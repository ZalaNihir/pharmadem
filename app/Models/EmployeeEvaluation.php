<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeEvaluation extends Model
{
    protected $fillable = ['employee_id', 'manager_id', 'period_start', 'period_end', 'rating', 'feedback', 'tasks_assigned', 'tasks_completed', 'tasks_overdue', 'completion_rate'];

    protected function casts(): array
    {
        return ['period_start' => 'date', 'period_end' => 'date', 'rating' => 'integer', 'completion_rate' => 'decimal:2'];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }
}
