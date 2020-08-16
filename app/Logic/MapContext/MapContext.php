<?php


namespace App\Logic\MapContext;

use App\Http\Controllers\Traits\ListsEnemies;
use App\Models\CharacterClass;
use App\Models\Faction;
use App\Models\Floor;
use App\Models\MapIconType;
use App\Models\RaidMarker;
use App\Service\Cache\CacheService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\App;

abstract class MapContext
{
    use ListsEnemies;

    /** @var Model */
    protected Model $_context;

    /**
     * @var Floor
     */
    private Floor $_floor;

    function __construct(Model $context, Floor $floor)
    {
        $this->_context = $context;
        $this->_floor = $floor;
    }

    /**
     * @return string
     */
    public abstract function getType(): string;

    /**
     * @return bool
     */
    public abstract function isTeeming(): bool;

    /**
     * @return int
     */
    public abstract function getSeasonalIndex(): int;

    /**
     * @return array
     */
    public abstract function getEnemies(): array;

    /**
     * @return array
     */
    public function toArray(): array
    {
        /** @var CacheService $cacheService */
        $cacheService = App::make(CacheService::class);

        // Get the DungeonData
        $dungeonData = $cacheService->getOtherwiseSet(sprintf('dungeon_%s', $this->_context->id), function ()
        {
            $dungeon = $this->_floor->dungeon->load(['enemies', 'enemypacks', 'enemypatrols', 'mapicons']);

            return array_merge($this->_floor->dungeon->toArray(), $this->getEnemies(), [
                'enemies'                   => $dungeon->enemies,
                'enemyPacks'                => $dungeon->enemypacks()->with(['enemies:enemies.id,enemies.enemy_pack_id,enemies.lat,enemies.lng'])->get(),
                'enemyPatrols'              => $dungeon->enemypatrols,
                'mapIcons'                  => $dungeon->mapicons,
                'dungeonFloorSwitchMarkers' => $dungeon->floorswitchmarkers
            ]);
        });

        $static = $cacheService->getOtherwiseSet('static_data', function ()
        {
            return [
                'mapIconTypes'                      => MapIconType::all(),
                'unknownMapIconType'                => MapIconType::find(1),
                'awakenedObeliskGatewayMapIconType' => MapIconType::find(11),
                'classColors'                       => CharacterClass::all()->pluck('color'),
                'raidMarkers'                       => RaidMarker::all(),
                'factions'                          => Faction::where('name', '<>', 'Unspecified')->with('iconfile')->get(),
            ];
        });

        return [
            'type'          => $this->getType(),
            'floorId'       => $this->_floor->id,
            'teeming'       => $this->isTeeming(),
            'seasonalIndex' => $this->getSeasonalIndex(),
            'dungeon'       => $dungeonData,
            'static'        => $static,
            // @TODO Probably move this? Temp fix
            'npcsMinHealth' => $this->_floor->dungeon->getNpcsMinHealth(),
            'npcsMaxHealth' => $this->_floor->dungeon->getNpcsMaxHealth()
        ];
    }
}