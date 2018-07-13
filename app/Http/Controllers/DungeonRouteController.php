<?php

namespace App\Http\Controllers;

use App\Http\Requests\DungeonRouteFormRequest;
use App\Models\Dungeon;

class DungeonRouteController extends BaseController
{
    public function __construct()
    {
        parent::__construct('dungeonroute');
    }

    public function getNewHeaderTitle()
    {
        return __('New dungeonroute');
    }

    public function getEditHeaderTitle()
    {
        return __('Edit dungeonroute');
    }

    public function new()
    {
        $this->_addVariable("dungeons", Dungeon::all());

        return parent::new();
    }

    /**
     * @param DungeonRouteFormRequest $request
     * @param int $id
     * @return mixed
     * @throws \Exception
     */
    public function store($request, int $id = -1)
    {
        dd($request);

        $dungeonroute = new DungeonRoute();
        $edit = $id !== -1;

        $dungeonroute->name = $request->get('name');
        // May not be set when editing
        $dungeonroute->expansion_id = $request->get('expansion');

        // Update or insert it
        if (!$dungeonroute->save()) {
            abort(500, 'Unable to save dungeon');
        }

        \Session::flash('status', sprintf(__('Dungeon %s'), $edit ? __("updated") : __("saved")));

        return $dungeonroute->id;
    }

    /**
     * Override to give the type hint which is required.
     *
     * @param DungeonRouteFormRequest $request
     * @return \Illuminate\Http\RedirectResponse
     * @throws \Exception
     */
    public function savenew(DungeonRouteFormRequest $request)
    {
        // Store it and show the edit page for the new item upon success
        return parent::_savenew($request);
    }
}
