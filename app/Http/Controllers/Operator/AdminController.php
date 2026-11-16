<?php

namespace App\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminController extends Controller
{
    public function index(): View
    {
        return view('operator.admins.index', [
            'admins' => User::query()->where('role', 'admin')->orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        return view('operator.admins.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'lowercase', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', 'string', 'min:8'],
        ]);

        $admin = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
        ]);

        $admin->forceFill([
            'role' => 'admin',
            'status' => 'active',
            'created_by' => $request->user()->getKey(),
        ])->save();

        return redirect()->route('operator.admins.index')->with('status', 'Admin created.');
    }

    public function edit(User $user): View
    {
        abort_unless($user->isAdmin(), 404);

        return view('operator.admins.edit', ['admin' => $user]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        abort_unless($user->isAdmin(), 404);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'lowercase', 'max:255', Rule::unique('users')->ignore($user)],
            'password' => ['nullable', 'confirmed', 'string', 'min:8'],
        ]);

        $user->fill([
            'name' => $validated['name'],
            'email' => $validated['email'],
        ]);

        if (filled($validated['password'] ?? null)) {
            $user->password = Hash::make($validated['password']);
        }

        $user->save();

        return redirect()->route('operator.admins.index')->with('status', 'Admin updated.');
    }

    public function destroy(User $user): RedirectResponse
    {
        abort_unless($user->isAdmin(), 404);
        $user->delete();

        return redirect()->route('operator.admins.index')->with('status', 'Admin deleted.');
    }
}
