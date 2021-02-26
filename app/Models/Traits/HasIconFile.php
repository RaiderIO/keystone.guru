<?php

namespace App\Models\Traits;

use App\Models\File;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Http\UploadedFile;

/**
 * @property File $iconfile
 * @property $icon_file_id int
 *
 * @mixin Model
 */
trait HasIconFile
{
    /**
     * @return HasOne
     */
    function iconfile()
    {
        return $this->hasOne('App\Models\File', 'model_id')->where('model_class', '=', get_class($this));
    }

    /**
     * @param UploadedFile $file
     * @throws Exception
     */
    function saveUploadedFile(UploadedFile $file){

        // Delete the icon should it exist already
        if ($this->iconfile !== null) {
            $this->iconfile->delete();
        }

        $icon = File::saveFileToDB($file, $this, 'uploads');

        // Update the expansion to reflect the new file ID
        $this->icon_file_id = $icon->id;
        $this->save();
    }
}