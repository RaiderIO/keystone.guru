<?php


namespace App\Logic\MapContext;

use App\Http\Controllers\Traits\ListsEnemies;
use App\Models\Floor;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

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
        $dungeonCacheKey = sprintf('dungeon_%s', $this->_context->id);
        if (Cache::has($dungeonCacheKey)) {
            $dungeonData = Cache::get($dungeonCacheKey);
        } else {
            $dungeon = $this->_floor->dungeon->load(['enemies', 'enemypacks', 'enemypatrols', 'mapicons']);

            $dungeonData = array_merge($this->_floor->dungeon->toArray(), $this->getEnemies(), [
                'enemies'                   => $dungeon->enemies,
                'enemyPacks'                => $dungeon->enemypacks()->with(['enemies:enemies.id,enemies.enemy_pack_id,enemies.lat,enemies.lng'])->get(),
                'enemyPatrols'              => $dungeon->enemypatrols,
                'mapIcons'                  => $dungeon->mapicons,
                'dungeonFloorSwitchMarkers' => $dungeon->floorswitchmarkers
            ]);

            if (!env('APP_DEBUG')) {
                Cache::set($dungeonCacheKey, $dungeonData, \DateInterval::createFromDateString(config('keystoneguru.cache_ttl.dungeonData')));
            }
        }

        return [
            'type'          => $this->getType(),
            'floorId'       => $this->_floor->id,
            'teeming'       => $this->isTeeming(),
            'seasonalIndex' => $this->getSeasonalIndex(),
            'dungeon'       => $dungeonData,
            // @TODO Probably move this? Temp fix
            'npcsMinHealth' => $this->_floor->dungeon->getNpcsMinHealth(),
            'npcsMaxHealth' => $this->_floor->dungeon->getNpcsMaxHealth()
        ];
    }
}