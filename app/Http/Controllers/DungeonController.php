<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
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
    public function storeModel(DungeonFormRequest $request, $id = -1)
    {
        $dungeon = new Dungeon();
        $edit = $id !== -1;
        $file = $request->file('icon');
        $fileId = -1;

        if ($edit) {
            // Load the expansion
            $dungeon = $dungeon->find($id);
            // Along with the ID of it's existing file
            $fileId = $dungeon->icon->id;
        }

        $dungeon->name = $request->get('name');
        // May not be set when editing
        $dungeon->icon_file_id = $fileId;
        $dungeon->color = $request->get('color');

        // Update or insert it
        if ($dungeon->save()) {
            // Save was successful, now do any file handling that may be necessary
            if( $file !== null ) {
                try {
                    $icon = $this->saveFileToDB($file, $dungeon);

                    // Update the expansion to reflect the new file ID
                    $dungeon->icon_file_id = $icon->id;
                    $dungeon->save();
                } catch(\Exception $ex){
                    if(!$edit){
                        // Roll back the saving of the expansion since something went wrong with the file.
                        $dungeon->delete();
                    }
                    throw $ex;
                }
            }
        }
        // Something went wrong with saving
        else {
            abort(500, 'Unable to save dungeon');
        }

        \Session::flash('status', sprintf(__('Dungeon %s'), $edit ? __("updated") : __("saved")));

        return $dungeon->id;
    }


    public function new()
    {
        return parent::_new();
    }

    public function edit($id)
    {
        return parent::_edit($id);
    }


    
    /**
     * @param DungeonFormRequest $request
     * @param $id
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     * @throws \Exception
     */
    public function update(DungeonFormRequest $request, $id)
    {
        // Store it and show the edit page again
        return $this->edit($this->_store($request, $id));
    }

    /**
     * @param ExpansionFormRequest $request
     * @return \Illuminate\Http\RedirectResponse
     * @throws \Exception
     */
    public function savenew(ExpansionFormRequest $request)
    {
        // Store it and show the edit page for the new item upon success
        return redirect()->route("admin.expansion.edit", ["id" => $this->_store($request)]);
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
