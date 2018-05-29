<?php

namespace App\Http\Controllers;

use App\Http\Requests\DungeonFormRequest;
use App\Models\Dungeon;

class DungeonController extends BaseController
{
    public function __construct()
    {
        parent::__construct('dungeon');
    }

    public function getNewHeaderTitle()
    {
        return __('New dungeon');
    }

    public function getEditHeaderTitle()
    {
        return __('Edit dungeon');
    }

    /**
     * @param DungeonFormRequest $request
     * @param int $id
     * @return mixed
     * @throws \Exception
     */
    public function store($request, int $id = -1)
    {
        $dungeon = new Dungeon();
        $edit = $id !== -1;

        $dungeon->name = $request->get('name');
        // May not be set when editing
        $dungeon->expansion_id = $request->get('expansion');

        // Update or insert it
        if (!$dungeon->save()) {
            abort(500, 'Unable to save dungeon');
        }

        \Session::flash('status', sprintf(__('Dungeon %s'), $edit ? __("updated") : __("saved")));

        return $dungeon->id;
    }

    /**
     * Override to give the type hint which is required.
     *
     * @param DungeonFormRequest $request
     * @return \Illuminate\Http\RedirectResponse
     * @throws \Exception
     */
    public function savenew(DungeonFormRequest $request)
    {
        // Store it and show the edit page for the new item upon success
        return parent::_savenew($request);
    }
}
