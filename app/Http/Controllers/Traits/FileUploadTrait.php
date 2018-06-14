<?php

namespace App\Http\Controllers\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use App\Models\File;

trait FileUploadTrait
{

    /**
     * Saves a file to the database
     * @param $file UploadedFile The uploaded file element.
     * @param $model Model The model that wants to save this file.
     * @return \App\Models\File The newly saved file in the database.
     * @throws \Exception
     */
    public function saveFileToDB($file, $model)
    {
        $disk = 'public';

        $newFile = new File();
        $newFile->model_id = $model->id;
        $newFile->model_class = get_class($model);
        $newFile->disk = $disk;
        $newFile->path = $file->store($this->getUploadDirectory(), $disk);
        $saveResult = $newFile->save();

        if (!$saveResult) {
            // Remove the uploaded file from disk
            $newFile->deleteFromDisk();

            throw new \Exception("Unable to save file to DB!");
        }

        return $newFile;
    }
}