<?php

namespace App\Http\Controllers\Auth;

use App\Enums\ActivityAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\UpdatePasswordRequest;
use App\Models\ActivityLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class PasswordController extends Controller
{
    public function edit(Request $request): View
    {
        return view('auth.change-password', [
            'isForced' => $request->user()->must_change_password,
        ]);
    }

    public function update(UpdatePasswordRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $request->user()->update([
            'password' => $validated['password'],
            'must_change_password' => false,
        ]);

        // Recorded for Staff and the Administrator only; the password itself is never logged.
        ActivityLog::record($request->user(), ActivityAction::PasswordChanged, 'Changed their own password.', $request->user());

        return redirect()->route('dashboard')->with('success', 'Your password has been changed.');
    }
}
