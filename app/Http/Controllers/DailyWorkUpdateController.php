<?php

namespace App\Http\Controllers;

use App\Models\DailyWorkUpdate;
use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class DailyWorkUpdateController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('viewAny', DailyWorkUpdate::class);
        $query = DailyWorkUpdate::with(['employee', 'task']);
        if ($request->user()->hasRole('Employee')) {
            $query->where('user_id', $request->user()->id);
        } elseif ($request->user()->hasRole('Manager')) {
            $query->whereHas('employee', fn ($q) => $q->where('manager_id', $request->user()->id));
        }
        $updates = $query->orderByDesc('work_date')->paginate(20)->withQueryString();

        return view('updates.index', compact('updates'));
    }

    public function create(Request $request)
    {
        Gate::authorize('create', DailyWorkUpdate::class);
        $tasks = Task::where('assigned_to', $request->user()->id)->where('status', '!=', 'Completed')->orderBy('title')->get();

        return view('updates.form', ['workUpdate' => new DailyWorkUpdate, 'tasks' => $tasks]);
    }

    public function store(Request $request)
    {
        Gate::authorize('create', DailyWorkUpdate::class);
        $data = $request->validate(['work_date' => ['required', 'date', 'before_or_equal:today'], 'completed_work' => ['required', 'string', 'max:10000'], 'pending_work' => ['nullable', 'string', 'max:10000'], 'blockers' => ['nullable', 'string', 'max:10000'], 'task_id' => ['nullable', 'integer', 'exists:tasks,id']]);
        if (! empty($data['task_id'])) {
            abort_unless(Task::whereKey($data['task_id'])->where('assigned_to', $request->user()->id)->exists(), 403);
        }
        $request->user()->workUpdates()->create($data);

        return redirect()->route('updates.index')->with('status', 'Daily update submitted.');
    }

    public function edit(DailyWorkUpdate $update)
    {
        Gate::authorize('update', $update);
        abort_if($update->work_date->lt(today()->subDays(7)), 403, 'Updates can only be edited for seven days.');
        $tasks = Task::where('assigned_to', $update->user_id)->orderBy('title')->get();

        return view('updates.form', ['workUpdate' => $update, 'tasks' => $tasks]);
    }

    public function update(Request $request, DailyWorkUpdate $update)
    {
        Gate::authorize('update', $update);
        abort_if($update->work_date->lt(today()->subDays(7)), 403, 'Updates can only be edited for seven days.');
        $data = $request->validate(['work_date' => ['required', 'date', 'before_or_equal:today'], 'completed_work' => ['required', 'string', 'max:10000'], 'pending_work' => ['nullable', 'string', 'max:10000'], 'blockers' => ['nullable', 'string', 'max:10000'], 'task_id' => ['nullable', 'integer', 'exists:tasks,id']]);
        if (! empty($data['task_id'])) {
            abort_unless(Task::whereKey($data['task_id'])->where('assigned_to', $request->user()->id)->exists(), 403);
        }
        $update->update($data);

        return redirect()->route('updates.index')->with('status', 'Daily update saved.');
    }

    public function destroy(DailyWorkUpdate $update)
    {
        Gate::authorize('delete', $update);
        $update->delete();

        return back()->with('status', 'Daily update removed.');
    }
}
