<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Interfaces\FileUploadHandler;
use App\Http\Controllers\Traits\FileUploadTrait;
use App\Http\Requests\ExpansionFormRequest;
use App\Models\Expansion;

class ExpansionController extends Controller implements FileUploadHandler
{
    use FileUploadTrait;

    public function new()
    {
        $headerTitle = __('New expansion');
        return view('admin.expansion.edit', compact('headerTitle'));
    }

    public function edit($id)
    {
        $expansion = new Expansion();
        $expansion = $expansion->find($id);
        if ($expansion === null) {
            abort(500, 'Unable to load expansion');
        }
        $headerTitle = __('Edit expansion');
        return view('admin.expansion.edit', compact('expansion', 'headerTitle'));
    }

    public function update(ExpansionFormRequest $request, $id)
    {
        // Store it and show the edit page again
        return $this->edit($this->_store($request, $id));
    }

    public function savenew(ExpansionFormRequest $request)
    {
        // Store it and show the edit page for the new item upon success
        return redirect()->route("admin.expansion.edit", ["id" => $this->_store($request)]);
    }

    /**
     * @param ExpansionFormRequest $request
     * @param int $id
     * @return mixed
     * @throws \Exception
     */
    private function _store(ExpansionFormRequest $request, $id = -1)
    {
        $expansion = new Expansion();
        $edit = $id !== -1;
        $file = $request->file('icon');
        $fileId = -1;

        if ($edit) {
            // Load the expansion
            $expansion = $expansion->find($id);
            // Along with the ID of it's existing file
            $fileId = $expansion->icon->id;
        }

        $expansion->name = $request->get('name');
        // May not be set when editing
        $expansion->icon_file_id = $fileId;
        $expansion->color = $request->get('color');

        // Update or insert it
        if ($expansion->save()) {
            // Save was successful, now do any file handling that may be necessary
            if( $file !== null ) {
                try {
                    $icon = $this->saveFileToDB($file, $expansion);

                    // Update the expansion to reflect the new file ID
                    $expansion->icon_file_id = $icon->id;
                    $expansion->save();
                } catch(\Exception $ex){
                    if(!$edit){
                        // Roll back the saving of the expansion since something went wrong with the file.
                        $expansion->delete();
                    }
                    throw $ex;
                }
            }
        }
        // Something went wrong with saving
        else {
            abort(500, 'Unable to save expansion');
        }

        \Session::flash('status', sprintf(__('Expansion %s'), $edit ? __("updated") : __("saved")));

        return $expansion->id;
    }

    public function view()
    {
        $expansions = Expansion::select(['id', 'icon_file_id', 'name', 'color'])->with('icon')->get();

        return view('admin.expansion.view', compact('expansions'));
    }

    /**
     * Overriden from trait
     * @return string The path to the directory where we should upload the files to.
     */
    public function getUploadDirectory(){
        return 'expansions';
    }
}
