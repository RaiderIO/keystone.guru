<?php

namespace App\Http\Controllers\Dungeon;

use App\Http\Controllers\Controller;
use App\Models\Dungeon;
use Illuminate\Http\Request;

class DungeonExploreController extends Controller
{
    /**
     * @param Request $request
     * @return void
     */
    public function list(Request $request)
    {
        return view('dungeon.explore.list');
    }

    /**
     * @param Request $request
     * @param Dungeon $dungeon
     * @return void
     */
    public function view(Request $request, Dungeon $dungeon)
    {

    }
}
