<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AccountStatus;
use App\Enums\ActivityAction;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
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
            'roles' => UserRole::personnelRoles(),
        ]);
    }

    /**
     * New accounts get a temporary password that must be changed on first login.
     */
    public function store(StoreUserRequest $request): RedirectResponse
    {
        $validated = $request->validated();

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
            'roles' => UserRole::personnelRoles(),
        ]);
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $validated = $request->safe()->except('is_active');
        $validated['status'] = $request->boolean('is_active') ? AccountStatus::Active : AccountStatus::Inactive;

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

    private function temporaryPassword(): string
    {
        return Str::password(10, symbols: false);
    }
}
