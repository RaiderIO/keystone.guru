<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DungeonRouteController extends Controller
{
    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Http\Response
     */
    public function new(){
        return view('dungeonroute/new');
    }
}
