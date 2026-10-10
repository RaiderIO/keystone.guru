<?php

namespace App\Http\Middleware;

use App\Models\GameVersion\GameVersion;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpFoundation\Response;

/**
 * Keeps visitors out of the Compendium while their game version has no compendium data.
 */
class EnsureGameVersionHasCompendium
{
    public function handle(Request $request, Closure $next): Response
    {
        if (GameVersion::getUserOrDefaultGameVersion()->has_compendium) {
            return $next($request);
        }

        if ($request->ajax() || $request->expectsJson()) {
            abort(404);
        }

        Session::flash('warning', __('controller.compendium.flash.unavailable_for_game_version'));

        return redirect()->route('home');
    }
}
