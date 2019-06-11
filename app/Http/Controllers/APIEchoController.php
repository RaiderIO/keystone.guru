<?php

namespace App\Http\Controllers;

use App\Models\DungeonRoute;
use Illuminate\Http\Request;

class APIEchoController extends Controller
{
    public function members(Request $request, DungeonRoute $dungeonroute)
    {
        dd($dungeonroute);
    }
}
