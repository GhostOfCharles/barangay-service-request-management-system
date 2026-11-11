<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Restricts a route group to residents ("role:resident") or to Staff and the Administrator ("role:personnel").
 */
class EnsureRole
{
    public function handle(Request $request, Closure $next, string $audience): Response
    {
        $user = $request->user();

        $allowed = match ($audience) {
            'resident' => $user?->isResident(),
            'personnel' => $user?->isPersonnel(),
            default => false,
        };

        abort_unless($allowed, 403);

        return $next($request);
    }
}
