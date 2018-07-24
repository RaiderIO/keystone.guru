<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

/**
 * @property int $id
 * @property int $model_id
 * @property string $model_class
 * @property string $disk
 * @property string $path
 */
class File extends Model
{
    public $hidden = ['model_id', 'model_class', 'created_at', 'updated_at'];

    function delete(){
        if( parent::delete() ) {
            $this->deleteFromDisk();
        }
    }

    /**
     * Deletes the file from disk
     *
     * @note This does NOT remove the file from the database!
     * @return bool True if the file was successfully deleted, false if it was not.
     */
    public function deleteFromDisk(){
        return Storage::disk($this->disk)->delete($this->path);
    }

    /**
     * Get a full path on the file system of this file.
     * @return string The string containing the file path.
     */
    public function getFullPath(){
        // @TODO May need to do something with $this->disk here?
        return public_path($this->path);
    }

    /**
     * Get an URL for putting in the url() function in your view.
     * @return string The string containing the URL.
     */
    public function getURL(){
        // @TODO May need to do something with $this->disk here?
        return $this->path;
    }
}
