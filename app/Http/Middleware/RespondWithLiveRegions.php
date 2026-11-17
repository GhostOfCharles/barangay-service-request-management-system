<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * Answers the page-polling script (resources/js/live-poll.js).
 *
 * A poll is an ordinary GET of the page the user is on, sent with an X-Live-Regions header that
 * names the page's @fragment regions. The page's own route, middleware, controller, and policies
 * run unchanged (so a resident still only sees their own data); this middleware then returns just
 * those fragments as JSON instead of the whole page. Polls never write anything.
 *
 * Anything other than a rendered page (a redirect to the login screen, 403, 404, ...) is answered
 * with JSON telling the script to stop, so it never follows a redirect into another page.
 */
class RespondWithLiveRegions
{
    public const HEADER = 'X-Live-Regions';

    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->isMethod('GET') || ! $request->hasHeader(self::HEADER)) {
            return $next($request);
        }

        $response = $next($request);
        $view = $response->original ?? null;

        if ($response->getStatusCode() !== 200 || ! $view instanceof View) {
            return $this->stop($response);
        }

        $names = array_filter(array_map('trim', explode(',', (string) $request->header(self::HEADER))));

        /** @var array<string, string> $fragments */
        $fragments = $view->render(fn (View $rendered): array => $rendered->getFactory()->getFragments());

        return $this->noStore(new JsonResponse([
            'regions' => array_intersect_key($fragments, array_flip($names)),
        ]));
    }

    /**
     * Tell the script to stop polling: 401 when the session has ended, otherwise the original status.
     */
    private function stop(Response $response): JsonResponse
    {
        $status = $response->isRedirection() ? 401 : $response->getStatusCode();

        return $this->noStore(new JsonResponse(['stop' => true], $status >= 400 ? $status : 409));
    }

    private function noStore(JsonResponse $response): JsonResponse
    {
        $response->headers->set('Cache-Control', 'no-store, private');
        $response->setVary(self::HEADER, false);

        return $response;
    }
}
