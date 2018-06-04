<?php

namespace App\Http\Controllers;

use App\Http\Requests\FloorFormRequest;
use App\Models\Dungeon;
use App\Models\Floor;

class FloorController extends BaseController
{
    public function __construct()
    {
        parent::__construct('floor', 'admin.dungeon');
    }

    public function getNewHeaderTitle()
    {
        return __('New floor');
    }

    public function getEditHeaderTitle()
    {
        return __('Edit floor');
    }

    private function _setDungeonVariable($dungeonId){
        // Override so we can set the
        $this->_setVariables(array(
            'dungeon' => Dungeon::findOrFail($dungeonId)
        ));
    }

    /**
     * @param $dungeonid
     * @param $id
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function newfloor($dungeonid){
        $this->_setDungeonVariable($dungeonid);
        return parent::new();
    }

    /**
     * @param $dungeonid int
     * @param $id int
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function editfloor($dungeonid, $id){
        $this->_setDungeonVariable($dungeonid);
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
        $floor = new Floor();
        $edit = $id !== -1;

        $floor->name = $request->get('name');
        // May not be set when editing
        $floor->dungeon_id = $request->get('dungeon');

        // Update or insert it
        if (!$floor->save()) {
            abort(500, 'Unable to save floor');
        }

        \Session::flash('status', sprintf(__('Floor %s'), $edit ? __("updated") : __("saved")));

        return $floor->id;
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
        $this->_setVariables(['dungeonid' => $request->get('dungeon')]);

        // Store it and show the edit page for the new item upon success
        return parent::_savenew($request);
    }
}
