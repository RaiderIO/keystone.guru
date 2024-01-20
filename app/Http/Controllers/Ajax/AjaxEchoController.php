<?php

namespace App\Http\Controllers\Ajax;

use App\Http\Controllers\Controller;
use App\Models\DungeonRoute\DungeonRoute;
use Illuminate\Http\Request;

class AjaxEchoController extends Controller
{
    public function members(Request $request, DungeonRoute $dungeonRoute)
    {
        dd($dungeonRoute);
    }
}
