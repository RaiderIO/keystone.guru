<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Interfaces\FileUploadHandler;
use App\Http\Controllers\Traits\FileUploadTrait;
use App\Http\Requests\ExpansionFormRequest;
use App\Models\Expansion;

class ExpansionController extends BaseController implements FileUploadHandler
{
    use FileUploadTrait;

    public function __construct()
    {
        parent::__construct('expansion', 'admin');
    }

    public function getNewHeaderTitle()
    {
        return __('New expansion');
    }

    public function getEditHeaderTitle()
    {
        return __('Edit expansion');
    }

    /**
     * @param ExpansionFormRequest $request
     * @param int $id
     * @return mixed
     * @throws \Exception
     */
    public function store($request, int $id = -1)
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

    /**
     * Overriden from trait
     * @return string The path to the directory where we should upload the files to.
     */
    public function getUploadDirectory(){
        return 'expansions';
    }

    /**
     * Override to give the type hint which is required.
     *
     * @param ExpansionFormRequest $request
     * @return \Illuminate\Http\RedirectResponse
     * @throws \Exception
     */
    public function savenew(ExpansionFormRequest $request)
    {
        // Store it and show the edit page for the new item upon success
        return parent::_savenew($request);
    }
}
