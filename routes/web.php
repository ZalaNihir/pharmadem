<?php

use App\Http\Controllers\AdminUserController;
use App\Http\Controllers\DailyWorkUpdateController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EmployeeEvaluationController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\TaskController;
use App\Models\Task;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');
    Route::resource('tasks', TaskController::class);
    Route::resource('updates', DailyWorkUpdateController::class)->only(['index', 'create', 'store', 'edit', 'update', 'destroy'])->parameters(['updates' => 'update']);
    Route::get('reports', [ReportController::class, 'index'])->name('reports.index')->can('viewAny', Task::class);
    Route::get('reports/export', [ReportController::class, 'export'])->name('reports.export')->can('viewAny', Task::class);
    Route::resource('evaluations', EmployeeEvaluationController::class)->only(['index', 'create', 'store', 'show', 'destroy']);
    Route::get('admin/users', [AdminUserController::class, 'index'])->name('admin.users.index')->middleware('can:manage-users');
    Route::put('admin/users/{user}', [AdminUserController::class, 'update'])->name('admin.users.update')->middleware('can:manage-users');
});

require __DIR__.'/settings.php';
