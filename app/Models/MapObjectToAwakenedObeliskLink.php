<?php

namespace App\Models;

use Eloquent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property int $id
 * @property int $source_map_object_id
 * @property string $source_map_object_class_name
 * @property int $target_map_icon_type_id
 * @property int $target_map_icon_seasonal_index
 *
 * @property Model $sourcemapobject
 * @property MapIcon $targetmapicon
 *
 * @mixin Eloquent
 */
class MapObjectToAwakenedObeliskLink extends Model
{
    public $timestamps = false;
    public $visible = ['target_map_icon_type_id', 'target_map_icon_seasonal_index'];
    public $fillable = ['source_map_object_id', 'source_map_object_class_name', 'target_map_icon_type_id', 'target_map_icon_seasonal_index'];

    /**
     * @return HasOne
     */
    public function sourcemapobject(): HasOne
    {
        return $this->hasOne($this->source_map_object_class_name, 'id', $this->source_map_object_id);
    }

    /**
     * @return HasOne
     */
    public function targetmapicon(): HasOne
    {
        return $this->hasOne(MapIcon::class)
            ->where('floor_id', $this->sourcemapobject->floor_id)
            ->where('map_icon_type_id', $this->target_map_icon_type_id)
            ->where('seasonal_index', $this->target_map_icon_seasonal_index);
    }
}
