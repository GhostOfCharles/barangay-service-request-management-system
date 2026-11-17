<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Vite;
use Symfony\Component\HttpFoundation\Response;

/**
 * Browser security headers on every response (NFR Security), as checked by the MDN HTTP Observatory.
 *
 * The Content Security Policy lets pages load scripts, styles, fonts, and frames only from this site.
 * Bootstrap's built-in icons are inline SVG data images, and some form controls use style attributes,
 * so `data:` images and inline styles are allowed; inline scripts are not. While the Vite dev server is
 * running (`npm run dev`) the policy is skipped, because its scripts load from another port.
 */
class AddSecurityHeaders
{
    public const CONTENT_SECURITY_POLICY = "default-src 'self'; script-src 'self'; style-src 'self' 'unsafe-inline'; "
        ."img-src 'self' data: blob:; font-src 'self'; connect-src 'self'; frame-src 'self'; "
        ."object-src 'none'; base-uri 'self'; form-action 'self'; frame-ancestors 'self'";

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        $headers = $response->headers;

        $headers->set('X-Content-Type-Options', 'nosniff');
        // SAMEORIGIN, not DENY: the Verify Residency panel shows a PDF ID in a same-site iframe.
        $headers->set('X-Frame-Options', 'SAMEORIGIN');
        $headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        if (! Vite::isRunningHot()) {
            $headers->set('Content-Security-Policy', self::CONTENT_SECURITY_POLICY);
        }

        // Only on the live site: browsers remember HSTS for a year, which would break plain-http local testing.
        if (app()->isProduction()) {
            $headers->set('Strict-Transport-Security', 'max-age=31536000');
        }

        return $response;
    }
}
