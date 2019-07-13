<?php

namespace App\Http\Controllers;

use App\Http\Requests\DungeonFormRequest;
use App\Models\Dungeon;
use App\Models\Expansion;
use Illuminate\Http\Request;

class DungeonController extends Controller
{
    /**
     * @param DungeonFormRequest $request
     * @param Dungeon $dungeon
     * @return mixed
     * @throws \Exception
     */
    public function store($request, Dungeon $dungeon = null)
    {
        if ($dungeon === null) {
            $dungeon = new Dungeon();
        }

        /** @var Dungeon $dungeon */
        $dungeon->name = $request->get('name');
        $dungeon->enemy_forces_required = $request->get('enemy_forces_required');
        $dungeon->enemy_forces_required_teeming = $request->get('enemy_forces_required_teeming');
        // May not be set when editing
        $dungeon->expansion_id = $request->get('expansion_id');
        $dungeon->active = $request->get('active', 0);

        // Update or insert it
        if (!$dungeon->save()) {
            abort(500, 'Unable to save dungeon');
        }

        return $dungeon;
    }

    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function new()
    {
        return view('admin.dungeon.edit', [
            'expansions' => Expansion::all()->pluck('name', 'id'),
            'headerTitle' => __('New dungeon')
        ]);
    }

    /**
     * @param Request $request
     * @param Dungeon $dungeon
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function edit(Request $request, Dungeon $dungeon)
    {
        return view('admin.dungeon.edit', [
            'expansions' => Expansion::all()->pluck('name', 'id'),
            'model' => $dungeon,
            'headerTitle' => __('Edit dungeon')
        ]);
    }

    /**
     * @param DungeonFormRequest $request
     * @param Dungeon $dungeon
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     * @throws \Exception
     */
    public function update(DungeonFormRequest $request, Dungeon $dungeon)
    {
        // Store it and show the edit page again
        $dungeon = $this->store($request, $dungeon);

        // Message to the user
        \Session::flash('status', __('Dungeon updated'));

        // Display the edit page
        return $this->edit($request, $dungeon);
    }

    /**
     * @param DungeonFormRequest $request
     * @return \Illuminate\Http\RedirectResponse
     * @throws \Exception
     */
    public function savenew(DungeonFormRequest $request)
    {
        // Store it and show the edit page
        $dungeon = $this->store($request);

        // Message to the user
        \Session::flash('status', __('Dungeon created'));

        return redirect()->route('admin.dungeon.edit', ["dungeon" => $dungeon]);
    }

    /**
     * Handles the viewing of a collection of items in a table.
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\
     */
    public function list()
    {
        return view('admin.dungeon.list', ['models' => Dungeon::orderByDesc('active')->get()]);
    }
}
