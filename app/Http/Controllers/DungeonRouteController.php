<?php

namespace App\Http\Controllers;

use App\Http\Requests\DungeonRouteFormRequest;
use App\Models\Dungeon;
use App\Models\DungeonRoute;
use App\Models\DungeonRoutePlayerRace;
use App\Models\DungeonRoutePlayerClass;

class DungeonRouteController extends BaseController
{
    public function __construct()
    {
        parent::__construct('dungeonroute', '\App\Models\DungeonRoute');
    }

    public function getNewHeaderTitle()
    {
        return __('New dungeonroute');
    }

    public function getEditHeaderTitle()
    {
        return __('Edit dungeonroute');
    }

    /**
     * Redirect new to a 'new' page, since the new page is different from the edit page in this case.
     * @return string
     */
    protected function _getNewActionView(){
        return 'new';
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
        $dungeonroute = new DungeonRoute();
        $edit = $id !== -1;

        $dungeonroute->dungeon_id = $request->get('dungeon');
        // May not be set when editing
        $dungeonroute->faction = $request->get('faction');

        // Update or insert it
        if ($dungeonroute->save()) {
            // We don't _really_ care if this doesn't get saved properly, they can just set it again when editing.
            foreach($request->get('race') as $key => $value){
                $drpRace = new DungeonRoutePlayerRace();
                $drpRace->index = $key;
                $drpRace->race_id = $value;
                $drpRace->dungeonroute_id = $dungeonroute->id;
                $drpRace->save();
            }

            foreach($request->get('class') as $key => $value){
                $drpRace = new DungeonRoutePlayerClass();
                $drpRace->index = $key;
                $drpRace->class_id = $value;
                $drpRace->dungeonroute_id = $dungeonroute->id;
                $drpRace->save();
            }
        } else {
            abort(500, 'Unable to save dungeon');
        }

        \Session::flash('status', sprintf(__('Dungeonroute %s'), $edit ? __("updated") : __("saved")));

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
