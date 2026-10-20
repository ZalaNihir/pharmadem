<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('viewAny', Task::class);

        return view('reports.index', ['rows' => $this->rows($request), 'employees' => $this->employees($request)]);
    }

    public function export(Request $request): StreamedResponse
    {
        Gate::authorize('viewAny', Task::class);
        $rows = $this->rows($request);

        return response()->streamDownload(function () use ($rows): void {
            $output = fopen('php://output', 'w');
            fputcsv($output, ['Employee', 'Assigned', 'Completed', 'Pending', 'Overdue']);
            foreach ($rows as $row) {
                fputcsv($output, array_map($this->safeCell(...), [$row['employee'], $row['assigned'], $row['completed'], $row['pending'], $row['overdue']]));
            }
            fclose($output);
        }, 'employee-task-report.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function rows(Request $request): array
    {
        return $this->employees($request)->map(function (User $employee) use ($request): array {
            $query = $employee->assignedTasks();
            if ($request->filled('from')) {
                $query->whereDate('created_at', '>=', $request->input('from'));
            }
            if ($request->filled('to')) {
                $query->whereDate('created_at', '<=', $request->input('to'));
            }
            $all = (clone $query)->count();
            $completed = (clone $query)->where('status', 'Completed')->count();

            return ['employee' => $employee->name, 'assigned' => $all, 'completed' => $completed, 'pending' => $all - $completed, 'overdue' => (clone $query)->where('status', '!=', 'Completed')->whereDate('due_date', '<', today())->count()];
        })->all();
    }

    private function employees(Request $request)
    {
        $query = User::role('Employee')->orderBy('name');
        if ($request->user()->hasRole('Manager')) {
            $query->where('manager_id', $request->user()->id);
        } elseif ($request->user()->hasRole('Employee')) {
            $query->whereKey($request->user()->id);
        }
        if ($request->filled('employee')) {
            $query->whereKey($request->integer('employee'));
        }

        return $query->get();
    }

    private function safeCell(mixed $value): string|int
    {
        if (is_string($value) && preg_match('/^[\s]*[=+\-@]/', $value)) {
            return "'".$value;
        }

        return $value;
    }
}
