<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class AdminUserController extends Controller
{
    public function index()
    {
        Gate::authorize('manage-users');

        return view('admin.users', ['users' => User::with(['manager', 'roles'])->orderBy('name')->paginate(20), 'managers' => User::role('Manager')->orderBy('name')->get()]);
    }

    public function update(Request $request, User $user)
    {
        Gate::authorize('manage-users');
        $data = $request->validate(['role' => ['required', Rule::in(['Admin', 'Manager', 'Employee'])], 'manager_id' => ['nullable', 'integer', 'exists:users,id']]);
        abort_if($user->id === $request->user()->id && $data['role'] !== 'Admin', 422, 'You cannot remove your own administrator access.');
        if ($data['role'] === 'Employee' && $data['manager_id']) {
            abort_unless(User::role('Manager')->whereKey($data['manager_id'])->exists(), 422, 'The selected user is not a manager.');
        }
        $user->syncRoles([$data['role']]);
        $user->manager_id = $data['role'] === 'Employee' ? ($data['manager_id'] ?? null) : null;
        $user->save();

        return back()->with('status', 'User access updated.');
    }
}
