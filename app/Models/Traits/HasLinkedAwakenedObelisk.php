<?php

namespace App\Models\Traits;

use App\Models\MapIcon;
use App\Models\MapObjectToAwakenedObeliskLink;
use Eloquent;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

/**
 * @property Collection|MapObjectToAwakenedObeliskLink[] $linkedawakenedobelisks
 *
 * @mixin Eloquent
 */
trait HasLinkedAwakenedObelisk
{
    /**
     * @return HasMany
     */
    function linkedawakenedobelisks()
    {
        return $this->hasMany('App\Models\MapObjectToAwakenedObeliskLink', 'source_map_object_id')
            ->where('source_map_object_class_name', get_class($this));
    }

    /**
     * Gets the ID of the awakened obelisk that this model is linked to.
     * @return int|null Null if not linked to any awakened obelisk.
     */
    public function getLinkedAwakenedObeliskIdAttribute()
    {
        $result = null;

        $linkedAwakenedObelisks = $this->linkedawakenedobelisks;
        /** @var null|MapObjectToAwakenedObeliskLink $linkedAwakenedObelisk */
        $linkedAwakenedObelisk = $linkedAwakenedObelisks->first();

        if ($linkedAwakenedObelisk !== null) {
            $mapIcon = MapIcon::where('floor_id', $this->floor_id)
                ->where('map_icon_type_id', $linkedAwakenedObelisk->target_map_icon_type_id)
                ->where('seasonal_index', $linkedAwakenedObelisk->target_map_icon_seasonal_index)
                ->get()->first();
            if ($mapIcon !== null) {
                $result = $mapIcon->id;
            }
        }

        return $result;
    }

    /**
     * Adds a link to an awakened obelisk by its map icon ID.
     * @param int|null $mapIconId Null to unset any previous relation.
     * @return bool True if a new relation was added successfully, false otherwise
     */
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

        return $result || $mapIcon === null;
    }

    public static function boot()
    {
        parent::boot();

        // Delete Path properly if it gets deleted
        static::deleting(function ($item)
        {
            /** @var $item HasLinkedAwakenedObelisk */
            if ($item->linkedawakenedobelisks !== null) {
                $item->linkedawakenedobelisks()->delete();
            }
        });
    }
}