<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use App\Http\Requests\DungeonFormRequest;
use App\Models\Dungeon;

class DungeonController extends Controller
{
    public function new(){
        return view('admin.dungeon.new');
    }

    public function store(DungeonFormRequest $request){
        $dungeon = new Dungeon();

        $dungeon->name = $request->get('name');
        $dungeon->key = $request->get('key');

        if( $dungeon->save() ){
            \Session::flash('status', 'Dungeon saved!');
        } else {
            abort(500, 'Unable to save dungeon');
        }

        return view('admin.dungeon.new');
    }

    public function view(){
        $dungeons = DB::table('dungeons')->select(['id', 'name', 'key'])->get();

        return view('admin.dungeon.view', compact('dungeons'));
    }
}
