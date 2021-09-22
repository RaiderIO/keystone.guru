<?php

namespace App\Models;

use App\Models\Traits\HasIconFile;
use Carbon\Carbon;
use Eloquent;
use Exception;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * @property int $id
 * @property int $icon_file_id
 * @property int $active
 * @property string $name
 * @property string $shortname
 * @property string $color
 *
 * @property Carbon $released_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 *
 * @property Collection|Dungeon[] $dungeons
 *
 * @mixin Eloquent
 */
class Expansion extends CacheModel
{
    use HasIconFile;

    public $fillable = ['active', 'icon_file_id', 'name', 'shortname', 'color', 'released_at'];

    public $hidden = ['id', 'icon_file_id', 'created_at', 'updated_at'];

    protected $dates = [
        // 'released_at',
        'created_at',
        'updated_at',
    ];

    const EXPANSION_LEGION      = 'legion';
    const EXPANSION_BFA         = 'bfa';
    const EXPANSION_SHADOWLANDS = 'shadowlands';

    const ALL = [
        'Legion'             => self::EXPANSION_LEGION,
        'Battle for Azeroth' => self::EXPANSION_BFA,
        'Shadowlands'        => self::EXPANSION_SHADOWLANDS,
    ];

    /**
     * https://stackoverflow.com/a/34485411/771270
     * @return string
     */
    public function getRouteKeyName()
    {
        return 'shortname';
    }

    /**
     * @return HasMany
     */
    public function dungeons()
    {
        return $this->hasMany('App\Models\Dungeon');
    }

    /**
     * Scope a query to only include active dungeons.
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeActive($query)
    {
        return $query->where('expansions.active', 1);
    }

    /**
     * Scope a query to only include inactive dungeons.
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeInactive($query)
    {
        return $query->where('expansions.active', 0);
    }

    /**
     * Saves an expansion with the data from a Request.
     *
     * @param Request $request
     * @param string $fileUploadDirectory
     * @return bool
     * @throws Exception
     */
    public function saveFromRequest(Request $request, string $fileUploadDirectory = 'uploads'): bool
    {
        $new = isset($this->id);

        $file = $request->file('icon');

        $this->icon_file_id = -1;
        $this->active       = $request->get('active');
        $this->name         = $request->get('name');
        $this->shortname    = $request->get('shortname');
        $this->color        = $request->get('color');

        // Update or insert it
        if ($this->save()) {
            // Save was successful, now do any file handling that may be necessary
            if ($file !== null) {
                try {
                    $icon = File::saveFileToDB($file, $this, $fileUploadDirectory);

                    // Update the expansion to reflect the new file ID
                    $this->icon_file_id = $icon->id;
                    $this->save();
                } catch (Exception $ex) {
                    if ($new) {
                        // Roll back the saving of the expansion since something went wrong with the file.
                        $this->delete();
                    }
                    throw $ex;
                }
            }

            return true;
        }

        return false;
    }
}
