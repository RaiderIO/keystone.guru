<?php

namespace App\Http\Controllers;

use App\Models\GameVersion\GameVersion;
use App\Service\GameVersion\GameVersionServiceInterface;
use Auth;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Redirect;

class GameVersionController extends Controller
{
    public function update(
        Request                     $request,
        GameVersion                 $gameVersion,
        GameVersionServiceInterface $gameVersionService,
    ): RedirectResponse {
        $previousGameVersion = $gameVersionService->getGameVersion(Auth::user());
        $gameVersionService->setGameVersion($gameVersion, Auth::user());

        // If the referer page's route contains "dungeonroutes" we redirect to the "dungeonroutes" route instead
        $referer = $request->headers->get('referer');
        if ($referer) {
            if (str_contains($referer, sprintf('/routes/%s', $previousGameVersion->key))) {
                return redirect()->route('dungeonroutes.current');
            } elseif (str_contains($referer, sprintf('/explore/%s', $previousGameVersion->key))) {
                return redirect()->route('dungeon.explore.list');
            }
        }

        return Redirect::back();
    }
}
