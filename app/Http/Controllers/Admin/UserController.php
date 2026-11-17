<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AccountStatus;
use App\Enums\ActivityAction;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Basic user administration, Administrator only. Staff and Administrator accounts are created here;
 * resident accounts come from self-registration and are activated through residency verification.
 */
class UserController extends Controller
{
    public function index(): View
    {
        return view('admin.users.index', [
            'users' => User::orderByRaw("case when role = 'resident' then 1 else 0 end")->orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.users.create', [
            'roles' => self::personnelRoles(),
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
            'role' => ['required', Rule::enum(UserRole::class)->only(self::personnelRoles())],
        ]);

        $temporaryPassword = $this->temporaryPassword();

        $user = User::create([
            ...$validated,
            'password' => $temporaryPassword,
            'must_change_password' => true,
            'status' => AccountStatus::Active,
        ]);

        ActivityLog::record($request->user(), ActivityAction::UserCreated,
            "Created the {$user->role->label()} account {$user->username} with a temporary password.", $user,
            ActivityLog::differences([], self::snapshot($user)));

        return redirect()->route('admin.users.index')
            ->with('temporaryPassword', ['username' => $user->username, 'password' => $temporaryPassword]);
    }

    public function edit(User $user): View
    {
        abort_if($user->isResident(), 404);

        return view('admin.users.edit', [
            'user' => $user,
            'roles' => self::personnelRoles(),
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        abort_if($user->isResident(), 404);

        $isSelf = $request->user()->is($user);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:50', 'alpha_dash', Rule::unique('users', 'username')->ignore($user)],
            'position' => ['nullable', 'string', 'max:100'],
            'role' => ['required', Rule::enum(UserRole::class)->only(self::personnelRoles())],
            'is_active' => ['boolean'],
        ]);

        $isActive = $request->boolean('is_active');
        unset($validated['is_active']);
        $validated['status'] = $isActive ? AccountStatus::Active : AccountStatus::Inactive;

        if ($isSelf && ($validated['role'] !== UserRole::Administrator->value || ! $isActive)) {
            return back()->withInput()
                ->withErrors(['role' => 'You cannot remove your own Administrator role or deactivate your own account.']);
        }

        $before = self::snapshot($user);
        $user->update($validated);
        $changes = ActivityLog::differences($before, self::snapshot($user));

        if ($changes) {
            ActivityLog::record($request->user(), ActivityAction::UserUpdated, "Edited the account {$user->username}.", $user, $changes);
        }

        return redirect()->route('admin.users.index')->with('success', "Account {$user->username} has been updated.");
    }

    /**
     * Password reset from the "Forgot Password?" flow: issues a temporary password for any account.
     */
    public function resetPassword(Request $request, User $user): RedirectResponse
    {
        $temporaryPassword = $this->temporaryPassword();

        $user->update([
            'password' => $temporaryPassword,
            'must_change_password' => true,
        ]);

        // The temporary password itself is never logged.
        ActivityLog::record($request->user(), ActivityAction::PasswordReset,
            "Reset the password of {$user->username} and issued a temporary password.", $user);

        return redirect()->route('admin.users.index')
            ->with('temporaryPassword', ['username' => $user->username, 'password' => $temporaryPassword]);
    }

    /**
     * The fields shown in the Activity Log, by label.
     *
     * @return array<string, ?string>
     */
    private static function snapshot(User $user): array
    {
        return [
            'Name' => $user->name,
            'Username' => $user->username,
            'Position' => $user->position,
            'Role' => $user->role->label(),
            'Status' => $user->status->label(),
        ];
    }

    /**
     * @return list<UserRole>
     */
    private static function personnelRoles(): array
    {
        return [UserRole::Administrator, UserRole::Staff];
    }

    private function temporaryPassword(): string
    {
        return Str::password(10, symbols: false);
    }
}
