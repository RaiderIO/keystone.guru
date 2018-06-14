<?php

namespace App\Http\Controllers\Interfaces;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use App\Models\File;

interface FileUploadHandler {

    /**
     * Saves a file to the database
     * @param $file UploadedFile The uploaded file element.
     * @param $model Model The model that wants to save this file.
     * @return File The newly saved file in the database.
     * @throws \Exception
     */
    function saveFileToDB($file, $model);

    /**
     * Get the path of the directory where uploaded files will be saved to.
     * @return string The name of the directory, NOT STARTING OR ENDING WITH SLASHES. You can embed them in the string though.
     */
    function getUploadDirectory();
}