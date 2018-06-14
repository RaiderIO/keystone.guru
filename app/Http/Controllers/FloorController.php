<?php

namespace App\Http\Controllers;

use App\Http\Requests\FloorFormRequest;
use App\Models\Dungeon;
use App\Models\Floor;
use Illuminate\Http\Request;

class FloorController extends BaseController
{
    public function __construct()
    {
        parent::__construct('floor', 'admin');
    }

    public function getNewHeaderTitle()
    {
        return __('New floor');
    }

    public function getEditHeaderTitle()
    {
        return __('Edit floor');
    }

    private function _setDungeonVariable($dungeonId)
    {
        // Override so we can set the
        $this->_setVariables(array(
            'dungeon' => Dungeon::findOrFail($dungeonId)
        ));
    }

    /**
     * @param $request Request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function newfloor(Request $request)
    {
        $dungeon = $request->get("dungeon");
        $this->_setDungeonVariable($dungeon);
        $this->_addVariable('floors', Floor::all()->where('dungeon_id', '=', $dungeon));
        return parent::new();
    }

    /**
     * @param $id int
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function editfloor($id)
    {
        /** @var $floor Floor */
        $floor = Floor::findOrFail($id);
        $this->_setDungeonVariable($floor->dungeon_id);
        $this->_addVariable('floors', Floor::all()->where('dungeon_id', '=', $floor->dungeon_id)->where('id', '<>', $id));
        return parent::edit($id);
    }

    /**
     * @param FloorFormRequest $request
     * @param int $id
     * @return mixed
     * @throws \Exception
     */
    public function store($request, int $id = -1)
    {
        /** @var Floor $floor */
        $floor = Floor::findOrNew($id);
        $edit = $id !== -1;

        $floor->index = $request->get('index');
        $floor->name = $request->get('name');
        if( !$edit ){
            // May not be set when editing
            $floor->dungeon_id = $request->get('dungeon');
        }

        // Update or insert it
        if (!$floor->save()) {
            abort(500, 'Unable to save floor');
        } else {
            // Remove all existing relationships
            $floor->directConnectedFloors()->detach($request->get('connectedfloors'));
            $floor->reverseConnectedFloors()->detach($request->get('connectedfloors'));
            // Create a new direct relationship
            $floor->directConnectedFloors()->sync($request->get('connectedfloors'));
        }

        \Session::flash('status', sprintf(__('Floor %s'), $edit ? __("updated") : __("saved")));

        // Must set the variable to set it for the incoming redirect
        $this->_setDungeonVariable($floor->dungeon_id);
        $this->_addVariable('floors', Floor::all()->where('dungeon_id', '=', $floor->dungeon_id)->where('id', '<>', $id));
        return $floor->id;
    }

    /**
     * Override to give the type hint which is required.
     *
     * @param FloorFormRequest $request
     * @param int $id
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     * @throws \Exception
     */
    public function update(FloorFormRequest $request, $id){
        return parent::_update($request, $id);
    }

    /**
     * Override to give the type hint which is required.
     *
     * @param FloorFormRequest $request
     * @return \Illuminate\Http\RedirectResponse
     * @throws \Exception
     */
    public function savenew(FloorFormRequest $request)
    {
        $this->_setVariables(['dungeon' => $request->get('dungeon')]);

        // Store it and show the edit page for the new item upon success
        return parent::_savenew($request);
    }
}
