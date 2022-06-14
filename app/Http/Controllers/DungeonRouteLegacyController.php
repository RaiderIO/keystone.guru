<?php

namespace App\Http\Controllers;

use App\Models\DungeonRoute;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Class DungeonRouteLegacyController - contains all deprecated endpoints for dungeonroutes to keep the OG file clean
 * @package App\Http\Controllers
 * @author Wouter
 * @since 06/06/2022
 */
class DungeonRouteLegacyController extends Controller
{
    /**
     * @param Request $request
     * @param DungeonRoute $dungeonroute
     * @return RedirectResponse
     */
    public function viewold(Request $request, DungeonRoute $dungeonroute)
    {
        return redirect()->route('dungeonroute.view', [
            'dungeon'      => $dungeonroute->dungeon,
            'dungeonroute' => $dungeonroute,
            'title'        => Str::slug($dungeonroute->title),
        ]);
    }

    /**
     * @param Request $request
     * @param DungeonRoute $dungeonroute
     * @return RedirectResponse
     */
    public function edit(Request $request, DungeonRoute $dungeonroute)
    {
        return redirect()->route('dungeonroute.edit', [
            'dungeon'      => $dungeonroute->dungeon,
            'dungeonroute' => $dungeonroute,
            'title'        => Str::slug($dungeonroute->title),
        ]);
    }

    /**
     * @param Request $request
     * @param DungeonRoute $dungeonroute
     * @param string $floorIndex
     * @return RedirectResponse
     */
    public function editfloor(Request $request, DungeonRoute $dungeonroute, string $floorIndex)
    {
        return redirect()->route('dungeonroute.edit.floor', [
            'dungeon'      => $dungeonroute->dungeon,
            'dungeonroute' => $dungeonroute,
            'title'        => Str::slug($dungeonroute->title),
            'floorindex'   => $floorIndex,
        ]);
    }

    /**
     * @param Request $request
     * @param DungeonRoute $dungeonroute
     * @param string $floorIndex
     * @return RedirectResponse
     */
    public function embedold(Request $request, DungeonRoute $dungeonroute, string $floorIndex = '1')
    {
        return redirect()->route('dungeonroute.embed', [
            'dungeon'      => $dungeonroute->dungeon,
            'dungeonroute' => $dungeonroute,
            'title'        => $dungeonroute->title,
            'floorindex'   => $floorIndex,
        ]);
    }

    /**
     * @param Request $request
     * @param DungeonRoute $dungeonroute
     * @param string $floorIndex
     * @return RedirectResponse
     */
    public function viewfloorold(Request $request, DungeonRoute $dungeonroute, string $floorIndex)
    {
        return redirect()->route('dungeonroute.view.floor', [
            'dungeon'      => $dungeonroute->dungeon,
            'dungeonroute' => $dungeonroute,
            'title'        => Str::slug($dungeonroute->title),
            'floorindex'   => $floorIndex,
        ]);
    }

    /**
     * @param Request $request
     * @param DungeonRoute $dungeonroute
     * @param string $floorIndex
     * @return RedirectResponse
     */
    public function previewold(Request $request, DungeonRoute $dungeonroute, string $floorIndex)
    {
        return redirect()->route('dungeonroute.preview', [
            'dungeon'      => $dungeonroute->dungeon,
            'dungeonroute' => $dungeonroute,
            'title'        => Str::slug($dungeonroute->title),
            'floorindex'   => $floorIndex,
        ]);
    }

}
