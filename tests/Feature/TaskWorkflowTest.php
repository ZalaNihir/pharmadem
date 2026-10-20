<?php

use App\Models\DailyWorkUpdate;
use App\Models\Task;
use App\Models\User;
use Spatie\Permission\Models\Role;

function ensureTaskRole(string $name): void
{
    Role::findOrCreate($name, 'web');
}

test('manager can assign a task to a team employee and cannot assign across teams', function () {
    ensureTaskRole('Manager');
    ensureTaskRole('Employee');
    $manager = User::factory()->create();
    $manager->assignRole('Manager');
    $employee = User::factory()->create(['manager_id' => $manager->id]);
    $employee->assignRole('Employee');
    $outsider = User::factory()->create();
    $outsider->assignRole('Employee');

    $this->actingAs($manager)->post(route('tasks.store'), [
        'title' => 'Prepare report', 'assigned_to' => $employee->id, 'priority' => 'High', 'status' => 'To Do',
    ])->assertRedirect(route('tasks.index'));

    $this->assertDatabaseHas('tasks', ['title' => 'Prepare report', 'assigned_to' => $employee->id, 'assigned_by' => $manager->id]);
    $this->actingAs($manager)->post(route('tasks.store'), [
        'title' => 'Unauthorized assignment', 'assigned_to' => $outsider->id, 'priority' => 'High', 'status' => 'To Do',
    ])->assertNotFound();
    $this->assertDatabaseMissing('tasks', ['title' => 'Unauthorized assignment']);
});

test('employee can change task status and completion date follows status', function () {
    ensureTaskRole('Employee');
    $employee = User::factory()->create();
    $employee->assignRole('Employee');
    $task = Task::create(['title' => 'Ship feature', 'assigned_to' => $employee->id, 'assigned_by' => $employee->id, 'priority' => 'Medium', 'status' => 'To Do', 'due_date' => today()->subDay()]);

    expect($task->isOverdue())->toBeTrue();

    $this->actingAs($employee)->put(route('tasks.update', $task), ['status' => 'Completed', 'priority' => 'Urgent'])->assertRedirect();
    $task->refresh();
    expect($task->status)->toBe('Completed')->and($task->completed_at)->not->toBeNull()->and($task->isOverdue())->toBeFalse();
    expect($task->priority)->toBe('Medium');

    $this->put(route('tasks.update', $task), ['status' => 'In Progress'])->assertRedirect();
    expect($task->refresh()->completed_at)->toBeNull()->and($task->isOverdue())->toBeTrue();
});

test('employees cannot read another employees daily updates', function () {
    ensureTaskRole('Employee');
    $employee = User::factory()->create();
    $employee->assignRole('Employee');
    $other = User::factory()->create();
    $other->assignRole('Employee');
    $update = DailyWorkUpdate::create(['user_id' => $other->id, 'work_date' => today(), 'completed_work' => 'Private update']);

    $this->actingAs($employee)->get(route('updates.edit', $update))->assertForbidden();
    $this->assertDatabaseHas('daily_work_updates', ['id' => $update->id, 'user_id' => $other->id]);
});

test('employee daily updates are saved under the authenticated employee', function () {
    ensureTaskRole('Employee');
    $employee = User::factory()->create();
    $employee->assignRole('Employee');

    $this->actingAs($employee)->post(route('updates.store'), [
        'work_date' => today()->toDateString(),
        'completed_work' => 'Finished the assigned review.',
        'pending_work' => 'Final approval remains.',
        'blockers' => '',
    ])->assertRedirect(route('updates.index'));

    $this->assertDatabaseHas('daily_work_updates', [
        'user_id' => $employee->id,
        'completed_work' => 'Finished the assigned review.',
        'pending_work' => 'Final approval remains.',
    ]);
});
