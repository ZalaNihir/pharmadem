<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Task extends Model
{
    protected $fillable = ['title', 'description', 'assigned_to', 'assigned_by', 'priority', 'status', 'start_date', 'due_date', 'completed_at'];

    protected function casts(): array
    {
        return ['start_date' => 'date', 'due_date' => 'date', 'completed_at' => 'datetime'];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function isOverdue(): bool
    {
        return $this->status !== 'Completed' && $this->due_date?->isBefore(today()) === true;
    }
}
