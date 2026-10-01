<?php

namespace App\Http\Middleware;

use App\Models\GameVersion\GameVersion;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Symfony\Component\HttpFoundation\Response;

/**
 * Sends any request for a retired game version's URL to the same URL on the game version its content now lives
 * under, so old links and bookmarks keep working.
 */
class RedirectRetiredGameVersion
{
    private const string PARAMETER_NAME = 'gameVersion';

    public function handle(Request $request, Closure $next): Response
    {
        $route = $request->route();
        if (!$route instanceof Route || !$route->hasParameter(self::PARAMETER_NAME)) {
            return $next($request);
        }

        $gameVersion = $route->parameter(self::PARAMETER_NAME);
        if (is_string($gameVersion)) {
            $gameVersion = GameVersion::query()->where('key', $gameVersion)->first();
        }

        if (!$gameVersion instanceof GameVersion || !$gameVersion->isRetired()) {
            return $next($request);
        }

        $retiredIntoGameVersion = $gameVersion->retiredIntoGameVersion;
        if ($retiredIntoGameVersion === null) {
            return $next($request);
        }

        $parameters                       = $route->parameters();
        $parameters[self::PARAMETER_NAME] = $retiredIntoGameVersion->key;

        $url         = url()->toRoute($route, $parameters, true);
        $queryString = $request->getQueryString();
        if ($queryString !== null) {
            $url = sprintf('%s?%s', $url, $queryString);
        }

        // 308 rather than 301 for anything but a read, so a POST stays a POST with its body intact
        return redirect()->to($url, $request->isMethodSafe() ? 301 : 308);
    }
}
