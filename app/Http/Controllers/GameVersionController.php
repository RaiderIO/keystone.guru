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
        GameVersionServiceInterface $gameVersionService
    ): RedirectResponse {
        $gameVersionService->setGameVersion($gameVersion, Auth::user());

        return Redirect::back();
    }
}
