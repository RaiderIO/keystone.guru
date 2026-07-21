<?php

namespace App\Repositories\Database;

use App\Models\MapIcon;
use App\Models\MapIconType;
use App\Repositories\Interfaces\MapIconRepositoryInterface;
use Illuminate\Support\Collection;

class MapIconRepository extends DatabaseRepository implements MapIconRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(MapIcon::class);
    }

    public function isDungeonStart(int $id, int $mappingVersionId): bool
    {
        return MapIcon::where('id', $id)
            ->where('mapping_version_id', $mappingVersionId)
            ->where('map_icon_type_id', MapIconType::ALL[MapIconType::MAP_ICON_TYPE_DUNGEON_START])
            ->exists();
    }

    /**
     * @return Collection<int, array{id: int, text: string}>
     */
    public function getDungeonStartsForMappingVersion(int $mappingVersionId): Collection
    {
        return MapIcon::where('mapping_version_id', $mappingVersionId)
            ->where('map_icon_type_id', MapIconType::ALL[MapIconType::MAP_ICON_TYPE_DUNGEON_START])
            ->get(['id', 'comment'])
            ->values()
            ->map(static fn(MapIcon $mapIcon, int $index) => [
                'id'   => $mapIcon->id,
                'text' => ($mapIcon->comment ?? '') !== '' ?
                    (string)__($mapIcon->comment) :
                    sprintf('%s #%d', __('mapicontypes.dungeon_start'), $index + 1),
            ]);
    }
}
