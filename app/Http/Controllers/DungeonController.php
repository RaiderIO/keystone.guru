<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\ChangesMapping;
use App\Http\Requests\DungeonFormRequest;
use App\Models\Dungeon;
use App\Models\Expansion;
use Exception;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Session;

class DungeonController extends Controller
{
    use ChangesMapping;

    /**
     * @param DungeonFormRequest $request
     * @param Dungeon|null $dungeon
     * @return mixed
     * @throws Exception
     */
    public function store($request, Dungeon $dungeon = null)
    {
        if ($dungeon === null) {
            $dungeon = new Dungeon();
        }

        $beforeDungeon = clone $dungeon;

        /** @var Dungeon $dungeon */
        // May not be set when editing
//        $dungeon->expansion_id = $request->get('expansion_id');
        $dungeon->zone_id                         = $request->get('zone_id');
        $dungeon->map_id                          = $request->get('map_id');
        $dungeon->mdt_id                          = $request->get('mdt_id');
        $dungeon->name                            = $request->get('name');
        $dungeon->slug                            = $request->get('slug');
        $dungeon->key                             = $request->get('key');
        $dungeon->enemy_forces_required           = $request->get('enemy_forces_required');
        $dungeon->enemy_forces_required_teeming   = $request->get('enemy_forces_required_teeming');
        $dungeon->enemy_forces_shrouded           = $request->get('enemy_forces_shrouded');
        $dungeon->enemy_forces_shrouded_zul_gamux = $request->get('enemy_forces_shrouded_zul_gamux');
        $dungeon->timer_max_seconds               = $request->get('timer_max_seconds');
        $dungeon->active                          = $request->get('active', 0);

        // Update or insert it
        if ($dungeon->save()) {
            $this->mappingChanged($beforeDungeon, $dungeon);
        } else {
            abort(500, 'Unable to save dungeon');
        }

        return $dungeon;
    }

    /**
     * @return Factory|View
     */
    public function new()
    {
        return view('admin.dungeon.edit', ['expansions' => Expansion::all()->pluck('name', 'id')]);
    }

    /**
     * @param Request $request
     * @param Dungeon $dungeon
     * @return Factory|View
     */
    public function edit(Request $request, Dungeon $dungeon)
    {
        return view('admin.dungeon.edit', [
            'expansions' => Expansion::all()->pluck('name', 'id'),
            'dungeon'    => $dungeon,
        ]);
    }

    /**
     * @param DungeonFormRequest $request
     * @param Dungeon $dungeon
     * @return Factory|View
     * @throws Exception
     */
    public function update(DungeonFormRequest $request, Dungeon $dungeon)
    {
        // Store it and show the edit page again
        $dungeon = $this->store($request, $dungeon);

        // Message to the user
        Session::flash('status', __('controller.dungeon.flash.dungeon_updated'));

        // Display the edit page
        return $this->edit($request, $dungeon);
    }

    /**
     * @param DungeonFormRequest $request
     * @return RedirectResponse
     * @throws Exception
     */
    public function savenew(DungeonFormRequest $request)
    {
        // Store it and show the edit page
        $dungeon = $this->store($request);

        // Message to the user
        Session::flash('status', __('controller.dungeon.flash.dungeon_created'));

        return redirect()->route('admin.dungeon.edit', ["dungeon" => $dungeon]);
    }

    /**
     * Handles the viewing of a collection of items in a table.
     *
     * @return Factory|
     */
    public function list()
    {
        return view('admin.dungeon.list', ['models' => Dungeon::orderByDesc('active')->get()]);
    }
}
