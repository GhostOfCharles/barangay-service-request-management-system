<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
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

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt([...$credentials, 'is_active' => true])) {
            return back()
                ->withInput($request->only('username'))
                ->withErrors(['username' => 'The username or password is incorrect, or the account is inactive.']);
        }

        $request->session()->regenerate();

        if ($request->user()->must_change_password) {
            return redirect()->route('password.edit');
        }

        return redirect()->intended(route('dashboard'));
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
