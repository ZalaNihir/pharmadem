<?php

namespace App\Http\Controllers;

use App\Models\EmployeeEvaluation;
use App\Models\Task;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class EmployeeEvaluationController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('viewAny', EmployeeEvaluation::class);
        $query = EmployeeEvaluation::with(['employee', 'manager'])->latest('period_end');
        if ($request->user()->hasRole('Manager')) {
            $query->whereHas('employee', fn ($q) => $q->where('manager_id', $request->user()->id));
        } elseif ($request->user()->hasRole('Employee')) {
            $query->where('employee_id', $request->user()->id);
        }

        return view('evaluations.index', ['evaluations' => $query->paginate(15)]);
    }

    public function create(Request $request)
    {
        Gate::authorize('create', EmployeeEvaluation::class);
        $employees = $request->user()->hasRole('Admin') ? User::role('Employee')->orderBy('name')->get() : $request->user()->employees()->role('Employee')->orderBy('name')->get();

        return view('evaluations.create', compact('employees'));
    }

    public function store(Request $request)
    {
        Gate::authorize('create', EmployeeEvaluation::class);
        $data = $request->validate(['employee_id' => ['required', 'integer', 'exists:users,id'], 'period_start' => ['required', 'date'], 'period_end' => ['required', 'date', 'after_or_equal:period_start'], 'rating' => ['required', 'integer', 'between:1,5'], 'feedback' => ['required', 'string', 'max:10000']]);
        $employeeQuery = User::role('Employee')->whereKey($data['employee_id']);
        if ($request->user()->hasRole('Manager')) {
            $employeeQuery->where('manager_id', $request->user()->id);
        }
        $employee = $employeeQuery->firstOrFail();
        $tasks = Task::where('assigned_to', $employee->id)->whereDate('created_at', '>=', $data['period_start'])->whereDate('created_at', '<=', $data['period_end']);
        $assigned = (clone $tasks)->count();
        $completed = (clone $tasks)->where('status', 'Completed')->count();
        $overdue = (clone $tasks)->where('status', '!=', 'Completed')->whereDate('due_date', '<', today())->count();
        $evaluation = EmployeeEvaluation::create($data + ['employee_id' => $employee->id, 'manager_id' => $request->user()->id, 'tasks_assigned' => $assigned, 'tasks_completed' => $completed, 'tasks_overdue' => $overdue, 'completion_rate' => $assigned ? round($completed / $assigned * 100, 2) : 0]);

        return redirect()->route('evaluations.index')->with('status', 'Evaluation recorded.');
    }

    public function show(EmployeeEvaluation $evaluation)
    {
        Gate::authorize('view', $evaluation);
        $evaluation->load(['employee', 'manager']);

        return view('evaluations.show', compact('evaluation'));
    }

    public function edit(EmployeeEvaluation $evaluation)
    {
        Gate::authorize('update', $evaluation);
        abort(404);
    }

    public function update(Request $request, EmployeeEvaluation $evaluation)
    {
        Gate::authorize('update', $evaluation);
        abort(404);
    }

    public function destroy(EmployeeEvaluation $evaluation)
    {
        Gate::authorize('delete', $evaluation);
        $evaluation->delete();

        return back()->with('status', 'Evaluation deleted.');
    }
}
