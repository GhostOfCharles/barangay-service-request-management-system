<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Middleware\EnsureAccountIsActive;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * One login page for all three roles (FR-01): check the credentials, then that the account is active,
     * then send each role to its own dashboard.
     */
    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credentials)) {
            return back()
                ->withInput($request->only('username'))
                ->withErrors(['username' => 'The username or password is incorrect.']);
        }

        $user = $request->user();

        if (! $user->isActive()) {
            $message = EnsureAccountIsActive::inactiveMessage($user);
            Auth::logout();

            return back()->withInput($request->only('username'))->withErrors(['username' => $message]);
        }

        $request->session()->regenerate();

        if ($user->must_change_password) {
            return redirect()->route('password.edit');
        }

        return redirect()->intended(route('home'));
    }

    /**
     * Shows the "contact the Administrator" message; passwords are reset by an Administrator.
     */
    public function forgotPassword(): View
    {
        return view('auth.forgot-password');
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('status', 'You have been logged out.');
    }
}
