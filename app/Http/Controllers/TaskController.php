<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class TaskController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('viewAny', Task::class);
        $query = Task::with(['employee', 'manager']);
        $user = $request->user();
        if ($user->hasRole('Manager')) {
            $query->whereHas('employee', fn ($q) => $q->where('manager_id', $user->id));
        } elseif ($user->hasRole('Employee')) {
            $query->where('assigned_to', $user->id);
        }
        foreach (['status', 'priority'] as $filter) {
            if ($request->filled($filter)) {
                $query->where($filter, $request->input($filter));
            }
        }
        if ($request->filled('employee') && $user->hasAnyRole(['Manager', 'Admin'])) {
            $query->where('assigned_to', $request->integer('employee'));
        }
        if ($request->filled('search')) {
            $query->where('title', 'like', '%'.$request->string('search').'%');
        }
        $tasks = $query->latest()->paginate(15)->withQueryString();
        $employees = $user->hasRole('Admin') ? User::role('Employee')->orderBy('name')->get() : $user->employees()->role('Employee')->orderBy('name')->get();

        return view('tasks.index', compact('tasks', 'employees'));
    }

    public function create(Request $request)
    {
        Gate::authorize('create', Task::class);
        $employees = $request->user()->hasRole('Admin') ? User::role('Employee')->orderBy('name')->get() : $request->user()->employees()->role('Employee')->orderBy('name')->get();

        return view('tasks.form', ['task' => new Task, 'employees' => $employees]);
    }

    public function store(Request $request)
    {
        Gate::authorize('create', Task::class);
        $data = $request->validate($this->taskRules());
        $employee = $this->authorizedEmployee($request, (int) $data['assigned_to']);
        $data['assigned_to'] = $employee->id;
        $data['assigned_by'] = $request->user()->id;
        $data['completed_at'] = $data['status'] === 'Completed' ? now() : null;
        Task::create($data);

        return redirect()->route('tasks.index')->with('status', 'Task created.');
    }

    public function show(Task $task)
    {
        Gate::authorize('view', $task);
        $task->load(['employee', 'manager']);

        return view('tasks.show', compact('task'));
    }

    public function edit(Request $request, Task $task)
    {
        Gate::authorize('update', $task);
        abort_unless($request->user()->hasAnyRole(['Admin', 'Manager']), 403);
        $employees = $request->user()->hasRole('Admin') ? User::role('Employee')->orderBy('name')->get() : $request->user()->employees()->role('Employee')->orderBy('name')->get();

        return view('tasks.form', compact('task', 'employees'));
    }

    public function update(Request $request, Task $task)
    {
        Gate::authorize('update', $task);
        if ($request->user()->hasRole('Employee')) {
            $data = $request->validate(['status' => ['required', Rule::in(['To Do', 'In Progress', 'Completed', 'Blocked'])]]);
        } else {
            $data = $request->validate($this->taskRules());
            $data['assigned_to'] = $this->authorizedEmployee($request, (int) $data['assigned_to'])->id;
        }
        $data['completed_at'] = ($data['status'] ?? $task->status) === 'Completed' ? ($task->completed_at ?? now()) : null;
        $task->update($data);

        return back()->with('status', 'Task updated.');
    }

    public function destroy(Task $task)
    {
        Gate::authorize('delete', $task);
        $task->delete();

        return redirect()->route('tasks.index')->with('status', 'Task deleted.');
    }

    private function taskRules(): array
    {
        return ['title' => ['required', 'string', 'max:255'], 'description' => ['nullable', 'string'], 'assigned_to' => ['required', 'integer', 'exists:users,id'], 'priority' => ['required', Rule::in(['Low', 'Medium', 'High', 'Urgent'])], 'status' => ['required', Rule::in(['To Do', 'In Progress', 'Completed', 'Blocked'])], 'start_date' => ['nullable', 'date'], 'due_date' => ['nullable', 'date', 'after_or_equal:start_date']];
    }

    private function authorizedEmployee(Request $request, int $id): User
    {
        $query = User::role('Employee')->whereKey($id);
        if ($request->user()->hasRole('Manager')) {
            $query->where('manager_id', $request->user()->id);
        }

        return $query->firstOrFail();
    }
}
