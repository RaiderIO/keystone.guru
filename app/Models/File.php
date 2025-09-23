<?php

namespace App\Models;

use Eloquent;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * @property int    $id
 * @property int    $model_id
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
    public $hidden = [
        'id',
        'disk',
        'path',
        'model_id',
        'model_class',
        'created_at',
        'updated_at',
        'pivot',
    ];

    /**
     * @var array Only this really matters when we're echoing the file.
     */
    public $appends = [
        'url',
        // @TODO remove this? Find usages in .js files though
        'icon_url',
    ];

    protected $fillable = [
        'model_id',
        'model_class',
        'disk',
        'path',
    ];

    /**
     * @return bool|null|void
     *
     * @throws Exception
     */
    public function delete(): void
    {
        if (parent::delete()) {
            $this->deleteFromDisk();
        }
    }

    /**
     * @return string Extend the file object with the full URL which is relevant for externals
     */
    public function getUrlAttribute(): string
    {
        return $this->getURL();
    }

    /**
     * @return string Gets the URL Attribute if this File is an Icon.
     */
    public function getIconUrlAttribute(): string
    {
        return $this->getURL();
    }

    /**
     * Deletes the file from disk
     *
     * @note This does NOT remove the file from the database!
     *
     * @return bool True if the file was successfully deleted, false if it was not.
     */
    public function deleteFromDisk(): bool
    {
        return Storage::disk($this->disk)->delete($this->path);
    }

    /**
     * Get a full path on the file system of this file.
     *
     * @return string The string containing the file path.
     */
    public function getFullPath(): string
    {
        $driver = config(sprintf('filesystems.disks.%s.driver', $this->disk));

        if ($driver !== 'local') {
            throw new \RuntimeException('getFullPath() is only available for local disks.');
        }

        return Storage::disk($this->disk)->path($this->path);
    }

    /**
     * Get an URL for putting in the url() function in your view.
     *
     * @return string The string containing the URL.
     */
    public function getURL(): string
    {
        return Storage::disk($this->disk)->url($this->path);
    }

    /**
     * Saves a file to the database
     *
     * @param  UploadedFile $uploadedFile The uploaded file element.
     * @param  Model        $model        The model that wants to save this file.
     * @param  string       $dir          The directory to save this file in.
     * @return File         The newly saved file in the database.
     *
     * @throws Exception
     */
    public static function saveFileToDB(
        UploadedFile $uploadedFile,
        Model        $model,
        string       $dir = '',
        string       $disk = null,
    ): File {
        // Use explicitly provided disk or fallback to default per environment
        $disk ??= config(
            'filesystems.default',
            'public',
        );

        // Store the file using Laravel's Storage facade
        $path = $uploadedFile->store($dir, $disk);

        $file = File::create([
            'model_id'    => $model->id,
            'model_class' => $model::class,
            'disk'        => $disk,
            'path'        => $path,
        ]);

        if (!$file->exists()) {
            // Delete uploaded file only if it was saved and DB insert fails
            Storage::disk($disk)->delete($path);

            throw new Exception('Unable to save file to DB!');
        }

        return $file;
    }

    protected static function boot(): void
    {
        parent::boot();

        static::deleting(function (File $file) {
            // Delete the file from disk when the model is deleted
            $file->deleteFromDisk();
        });
    }
}
