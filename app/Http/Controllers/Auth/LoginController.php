<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Middleware\EnsureAccountIsActive;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Throwable;

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

        return redirect()->to($this->landingUrl($request, $user));
    }

    /**
     * The page the user was trying to open before logging in, when it belongs to their role;
     * otherwise their own dashboard. This avoids landing a Staff user on an Administrator page
     * (403) that was open in the same browser before.
     */
    private function landingUrl(Request $request, User $user): string
    {
        $intended = $request->session()->pull('url.intended');

        if (! is_string($intended)) {
            return route('home');
        }

        try {
            $routeName = (string) app('router')->getRoutes()->match(Request::create($intended))->getName();
        } catch (Throwable) {
            return route('home');
        }

        $allowed = match (true) {
            str_starts_with($routeName, 'resident.') => $user->isResident(),
            str_starts_with($routeName, 'admin.') => $user->isAdministrator(),
            $routeName === 'home', str_starts_with($routeName, 'files.'), str_starts_with($routeName, 'password.') => true,
            default => $user->isPersonnel(),
        };

        return $allowed ? $intended : route('home');
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
