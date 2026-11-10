<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Basic user administration, visible to Administrators only.
 */
class UserController extends Controller
{
    public function index(): View
    {
        return view('admin.users.index', [
            'users' => User::orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.users.create', [
            'roles' => UserRole::cases(),
        ]);
    }

    /**
     * New accounts get a temporary password that must be changed on first login.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:50', 'alpha_dash', 'unique:users,username'],
            'position' => ['nullable', 'string', 'max:100'],
            'role' => ['required', Rule::enum(UserRole::class)],
        ]);

        $temporaryPassword = $this->temporaryPassword();

        $user = User::create([
            ...$validated,
            'password' => $temporaryPassword,
            'must_change_password' => true,
            'is_active' => true,
        ]);

        return redirect()->route('admin.users.index')
            ->with('temporaryPassword', ['username' => $user->username, 'password' => $temporaryPassword]);
    }

    public function edit(User $user): View
    {
        return view('admin.users.edit', [
            'user' => $user,
            'roles' => UserRole::cases(),
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $isSelf = $request->user()->is($user);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:50', 'alpha_dash', Rule::unique('users', 'username')->ignore($user)],
            'position' => ['nullable', 'string', 'max:100'],
            'role' => ['required', Rule::enum(UserRole::class)],
            'is_active' => ['boolean'],
        ]);

        $validated['is_active'] = $request->boolean('is_active');

        if ($isSelf && ($validated['role'] !== UserRole::Admin->value || ! $validated['is_active'])) {
            return back()->withInput()
                ->withErrors(['role' => 'You cannot remove your own Administrator role or deactivate your own account.']);
        }

        $user->update($validated);

        return redirect()->route('admin.users.index')->with('success', "Account {$user->username} has been updated.");
    }

    /**
     * Admin password reset from the "Forgot Password" flow: issues a temporary password.
     */
    public function resetPassword(User $user): RedirectResponse
    {
        $temporaryPassword = $this->temporaryPassword();

        $user->update([
            'password' => $temporaryPassword,
            'must_change_password' => true,
        ]);

        return redirect()->route('admin.users.index')
            ->with('temporaryPassword', ['username' => $user->username, 'password' => $temporaryPassword]);
    }

    private function temporaryPassword(): string
    {
        return Str::password(10, symbols: false);
    }
}
