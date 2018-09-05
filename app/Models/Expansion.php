<?php

namespace App\Models;

use Illuminate\Http\Request;

/**
 * @property int $id
 * @property int $icon_file_id
 * @property string $name
 * @property string $shortname
 * @property string $color
 */
class Expansion extends IconFileModel
{

    public $hidden = ['id', 'icon_file_id', 'created_at', 'updated_at'];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function dungeons()
    {
        return $this->hasMany('App\Models\Dungeon');
    }

    /**
     * Saves an expansion with the data from a Request.
     *
     * @param Request $request
     * @param string $fileUploadDirectory
     * @throws \Exception
     */
    public function saveFromRequest(Request $request, $fileUploadDirectory = 'uploads')
    {
        $new = isset($this->id);

        $file = $request->file('icon');

        $this->icon_file_id = -1;
        $this->name = $request->get('name');
        $this->shortname = $request->get('shortname');
        $this->color = $request->get('color');

        // Update or insert it
        if ($this->save()) {
            // Save was successful, now do any file handling that may be necessary
            if ($file !== null) {
                try {
                    $icon = File::saveFileToDB($file, $this, $fileUploadDirectory);

                    // Update the expansion to reflect the new file ID
                    $this->icon_file_id = $icon->id;
                    $this->save();
                } catch (\Exception $ex) {
                    if ($new) {
                        // Roll back the saving of the expansion since something went wrong with the file.
                        $this->delete();
                    }
                    throw $ex;
                }
            }
        }
    }
}
