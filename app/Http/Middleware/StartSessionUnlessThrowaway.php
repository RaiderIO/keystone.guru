<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Contracts\Session\Session;
use Illuminate\Http\Request;
use Illuminate\Session\Middleware\StartSession;
use Jenssegers\Agent\Agent;
use Symfony\Component\HttpFoundation\Response;

/**
 * Starts the session like Laravel's StartSession, but does not persist it (no store write, no cookies)
 * when it is a throwaway: a guest session that was created by this very request, holds nothing but the
 * framework's own bookkeeping, and belongs to a client that will never bring its cookie back.
 *
 * Those are crawlers, uptime/health checkers and cross-site iframes (embeds), where the browser refuses
 * to store a SameSite=lax/strict cookie. Persisting those keeps a never-read session in Redis for the full
 * session lifetime, one per request.
 */
class StartSessionUnlessThrowaway extends StartSession
{
    /** Keys Laravel writes into every session on its own; anything else means the request used the session. */
    private const array BOOKKEEPING_KEYS = [
        '_token',
        '_previous',
        '_flash',
    ];

    /**
     * @param  Session $session
     * @return mixed
     */
    protected function handleStatefulRequest(Request $request, $session, Closure $next)
    {
        $request->setLaravelSession(
            $this->startSession($request, $session),
        );

        $this->collectGarbage($session);

        $response = $next($request);

        if ($this->isThrowawaySession($request, $session)) {
            $this->removeCsrfCookieFromResponse($response);

            return $response;
        }

        $this->storeCurrentUrl($request, $session);

        $this->addCookieToResponse($response, $session);

        $this->saveSession($request);

        return $response;
    }

    private function isThrowawaySession(Request $request, Session $session): bool
    {
        return !$request->cookies->get($session->getName())
            && $this->holdsOnlyBookkeeping($session)
            && ($this->isNonBrowserClient($request) || $this->cannotStoreSessionCookie($request))
            && $request->user() === null;
    }

    private function holdsOnlyBookkeeping(Session $session): bool
    {
        return array_diff(array_keys($session->all()), self::BOOKKEEPING_KEYS) === []
            && empty($session->get('_flash.new'));
    }

    /**
     * Browsers send Fetch Metadata (Sec-Fetch-*) headers on every request, crawlers and health checkers
     * don't - requiring both keeps a browser whose user agent happens to match a crawler pattern from
     * never getting a session, which would leave it unable to log in.
     */
    private function isNonBrowserClient(Request $request): bool
    {
        if ($request->headers->has('Sec-Fetch-Site')) {
            return false;
        }

        $userAgent = trim($request->userAgent() ?? '');

        return $userAgent === '' || new Agent()->isRobot($userAgent);
    }

    /**
     * A browser does not store a SameSite=lax/strict cookie set by a cross-site response unless that response
     * is a top-level navigation - so an embed loaded in another site's iframe never gets its session back.
     */
    private function cannotStoreSessionCookie(Request $request): bool
    {
        $sameSite = strtolower((string)($this->manager->getSessionConfig()['same_site'] ?? 'lax'));

        return $sameSite !== 'none'
            && $request->headers->get('Sec-Fetch-Site') === 'cross-site'
            && $request->headers->get('Sec-Fetch-Dest') !== 'document';
    }

    private function removeCsrfCookieFromResponse(Response $response): void
    {
        $config = $this->manager->getSessionConfig();

        $response->headers->removeCookie('XSRF-TOKEN', $config['path'] ?? '/', $config['domain'] ?? null);
    }
}
