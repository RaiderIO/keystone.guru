<?php

namespace App\Models;

use Eloquent;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * @property int $id
 * @property int $model_id
 * @property string $model_class
 * @property string $disk
 * @property string $path
 *
 * @mixin Eloquent
 */
class File extends Model
{
    /**
     * @var array None of this really matters for externals
     */
    public $hidden = ['id', 'disk', 'path', 'model_id', 'model_class', 'created_at', 'updated_at'];

    /**
     * @var array Only this really matters when we're echoing the file.
     */
    public $appends = ['url', 'icon_url'];

    /**
     * @return bool|null|void
     * @throws Exception
     */
    function delete()
    {
        if (parent::delete()) {
            $this->deleteFromDisk();
        }
    }

    /**
     * @return string Extend the file object with the full URL which is relevant for externals
     */
    public function getUrlAttribute()
    {
        return $this->getURL();
    }

    /**
     * @return string Gets the URL Attribute if this File is an Icon.
     */
    public function getIconUrlAttribute()
    {
        return $this->getURL();
        // Unavailable since switching to different Image library - but we don't use it anyways
//        $iconUrl = '';
//        // Only if it's an image!
//        if(Image::format($this->getUrl()) !== null){
//            // Send as little data as possible, fetch the url, but strip it off the full path
//            $iconUrl = @parse_url(Image::url($this->getUrl(), 32, 32))['path'];
//        }
//        return $iconUrl;
    }

    /**
     * Deletes the file from disk
     *
     * @note This does NOT remove the file from the database!
     * @return bool True if the file was successfully deleted, false if it was not.
     */
    public function deleteFromDisk()
    {
        return Storage::disk($this->disk)->delete($this->path);
    }

    /**
     * Get a full path on the file system of this file.
     * @return string The string containing the file path.
     */
    public function getFullPath()
    {
        // @TODO May need to do something with $this->disk here?
        return public_path($this->path);
    }

    /**
     * Get an URL for putting in the url() function in your view.
     * @return string The string containing the URL.
     */
    public function getURL()
    {
        // @TODO May need to do something with $this->disk here?
        if (config('app.env') === 'local') {
            return url($this->path);
        } else {
            return url('storage/' . $this->path);
        }
    }

    /**
     * Saves a file to the database
     * @param $uploadedFile UploadedFile The uploaded file element.
     * @param $model Model The model that wants to save this file.
     * @param $dir string The directory to save this file in.
     * @return File The newly saved file in the database.
     * @throws Exception
     */
    public static function saveFileToDB($uploadedFile, $model, $dir = 'upload')
    {
        $disk = config('app.env') === 'local' ? 'public_uploads' : 'public';

        // Ensure the path exists
        $storageDir = Storage::disk($disk)->getAdapter()->getPathPrefix() . '/' . $dir;
        if (!is_dir($storageDir)) {
            mkdir($storageDir, 755, true);
        }

        $newFile = new File();
        $newFile->model_id = $model->id;
        $newFile->model_class = get_class($model);
        $newFile->disk = $disk;
        $newFile->path = $uploadedFile->store($dir, $disk);
        $saveResult = $newFile->save();

        if (!$saveResult) {
            // Remove the uploaded file from disk
            $newFile->deleteFromDisk();

            throw new Exception("Unable to save file to DB!");
        }

        return $newFile;
    }
}
