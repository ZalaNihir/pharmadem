<?php

namespace App\Http\Controllers;

use App\Models\DailyWorkUpdate;
use App\Models\Task;
use App\Models\User;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = $request->user();
        $query = Task::query();
        if ($user->hasRole('Manager')) {
            $query->whereHas('employee', fn ($q) => $q->where('manager_id', $user->id));
        } elseif ($user->hasRole('Employee') || ! $user->hasRole('Admin')) {
            $query->where('assigned_to', $user->id);
        }
        foreach (['status', 'priority'] as $filter) {
            if ($request->filled($filter)) {
                $query->where($filter, $request->input($filter));
            }
        }
        if ($request->filled('employee') && $user->hasAnyRole(['Admin', 'Manager'])) {
            $query->where('assigned_to', $request->integer('employee'));
        }
        if ($request->filled('from')) {
            $query->whereDate('created_at', '>=', $request->input('from'));
        }
        if ($request->filled('to')) {
            $query->whereDate('created_at', '<=', $request->input('to'));
        }
        $tasks = (clone $query)->with(['employee', 'manager'])->latest()->limit(8)->get();
        $stats = ['total' => (clone $query)->count(), 'todo' => (clone $query)->where('status', 'To Do')->count(), 'progress' => (clone $query)->where('status', 'In Progress')->count(), 'completed' => (clone $query)->where('status', 'Completed')->count(), 'overdue' => (clone $query)->where('status', '!=', 'Completed')->whereDate('due_date', '<', today())->count()];
        $team = $user->hasRole('Admin') ? User::role('Employee')->withCount(['assignedTasks as task_count', 'assignedTasks as completed_count' => fn ($q) => $q->where('status', 'Completed')])->orderBy('name')->get() : ($user->hasRole('Manager') ? $user->employees()->role('Employee')->withCount(['assignedTasks as task_count', 'assignedTasks as completed_count' => fn ($q) => $q->where('status', 'Completed')])->orderBy('name')->get() : collect());
        $updatesQuery = DailyWorkUpdate::with('employee')->latest('work_date');
        if ($user->hasRole('Manager')) {
            $updatesQuery->whereHas('employee', fn ($q) => $q->where('manager_id', $user->id));
        } elseif ($user->hasRole('Employee') || ! $user->hasRole('Admin')) {
            $updatesQuery->where('user_id', $user->id);
        }
        $recentUpdates = $updatesQuery->limit(6)->get();
        $upcoming = (clone $query)->whereNotNull('due_date')->where('status', '!=', 'Completed')->whereBetween('due_date', [today(), today()->addDays(7)])->orderBy('due_date')->limit(6)->with('employee')->get();
        $employees = $user->hasRole('Admin') ? User::role('Employee')->orderBy('name')->get() : ($user->hasRole('Manager') ? $user->employees()->role('Employee')->orderBy('name')->get() : collect());

        return view('dashboard', compact('stats', 'team', 'recentUpdates', 'upcoming', 'employees', 'tasks'));
    }
}
