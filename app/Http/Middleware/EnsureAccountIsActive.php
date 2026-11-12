<?php

namespace App\Http\Middleware;

use App\Enums\AccountStatus;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Signs out any account that is not Active: pending verification, rejected, or deactivated (FR-01, FR-09).
 */
class EnsureAccountIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && ! $user->isActive()) {
            $message = self::inactiveMessage($user);

            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors(['username' => $message]);
        }

        return $next($request);
    }

    /**
     * The 'pending verification' or 'inactive account' message shown at login.
     */
    public static function inactiveMessage(User $user): string
    {
        return match ($user->status) {
            AccountStatus::Pending => 'Your account is pending verification. Barangay staff will check your valid ID before you can log in.',
            AccountStatus::Rejected => 'Your registration was not approved'
                .($user->resident?->rejection_reason ? ': '.$user->resident->rejection_reason : '')
                .'. Please visit the barangay hall or register again with a clearer valid ID.',
            default => 'This account is inactive. Please contact the Administrator.',
        };
    }
}
