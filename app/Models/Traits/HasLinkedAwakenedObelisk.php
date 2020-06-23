<?php

namespace App\Models\Traits;

use App\Models\MapIcon;
use App\Models\MapObjectToAwakenedObeliskLink;
use Eloquent;
use Illuminate\Support\Collection;

/**
 * @property Collection|MapObjectToAwakenedObeliskLink[] $linkedawakenedobelisks
 *
 * @mixin Eloquent
 */
trait HasLinkedAwakenedObelisk
{
    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    function linkedawakenedobelisks()
    {
        return $this->hasMany('App\Models\MapObjectToAwakenedObeliskLink', 'source_map_object_id')
            ->where('source_map_object_class_name', get_class($this));
    }

    public function getLinkedAwakenedObeliskIdAttribute()
    {
        $result = null;

        $linkedAwakenedObelisks = $this->linkedawakenedobelisks;
        /** @var null|MapObjectToAwakenedObeliskLink $linkedAwakenedObelisk */
        $linkedAwakenedObelisk = $linkedAwakenedObelisks->first();

        if ($linkedAwakenedObelisk !== null) {
            $result = MapIcon::where('floor_id', $this->floor_id)
                ->where('map_icon_type_id', $linkedAwakenedObelisk->target_map_icon_type_id)
                ->where('seasonal_index', $linkedAwakenedObelisk->target_map_icon_seasonal_index)
                ->get()->first()->id;
        }

        return $result;
    }

    public function setLinkedAwakenedObeliskByMapIconId(?int $mapIconId)
    {
        $result = false;
        $mapIcon = MapIcon::find($mapIconId);
        // Delete any existing links
        MapObjectToAwakenedObeliskLink::where('source_map_object_id', $this->id)
            ->where('source_map_object_class_name', get_class($this))
            ->delete();

        // Insert new link
        if ($mapIcon !== null && $mapIcon->isAwakenedObelisk()) {
            $result = (new MapObjectToAwakenedObeliskLink([
                'source_map_object_id'           => $this->id,
                'source_map_object_class_name'   => get_class($this),
                'target_map_icon_type_id'        => $mapIcon->map_icon_type_id,
                'target_map_icon_seasonal_index' => $mapIcon->seasonal_index
            ]))->save();
        }

        return $result;
    }
}