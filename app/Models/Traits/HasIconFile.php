<?php

namespace App\Models\Traits;

use App\Models\File;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Http\UploadedFile;

/**
 * @property File $iconfile
 * @property int  $icon_file_id
 *
 * @mixin Model
 */
trait HasIconFile
{
    public function iconfile(): HasOne
    {
        return $this->hasOne(File::class, 'model_id')->where('model_class', $this::class);
    }

    /**
     * @throws Exception
     */
    public function saveUploadedFile(UploadedFile $file): void
    {
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
