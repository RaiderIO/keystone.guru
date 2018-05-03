<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DungeonController extends Controller
{
    //

    public function new(){
        return view('dungeon/new');
    }

    public function store(){
        return view('dungeon/new');
    }
}
