<?php


namespace App\Logic\MapContext;

use App\Http\Controllers\Traits\ListsEnemies;
use App\Models\CharacterClass;
use App\Models\Faction;
use App\Models\Floor;
use App\Models\MapIconType;
use App\Models\Mapping\MappingVersion;
use App\Models\PublishedState;
use App\Models\RaidMarker;
use App\Models\Spell;
use App\Service\Cache\CacheServiceInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Psr\SimpleCache\InvalidArgumentException;

abstract class MapContext
{
    use ListsEnemies;

    /** @var Model */
    protected Model $context;
    /** @var Floor */
    protected Floor $floor;
    /** @var MappingVersion */
    protected MappingVersion $mappingVersion;

    function __construct(Model $context, Floor $floor, MappingVersion $mappingVersion)
    {
        $this->context        = $context;
        $this->floor          = $floor;
        $this->mappingVersion = $mappingVersion;
    }

    /** @return string */
    public abstract function getType(): string;

    /** @return bool */
    public abstract function isTeeming(): bool;

    /** @return int */
    public abstract function getSeasonalIndex(): int;

    /** @return array */
    public abstract function getEnemies(): array;

    /** @return string */
    public abstract function getEchoChannelName(): string;

    /**
     * @return array
     * @throws InvalidArgumentException
     */
    public function getProperties(): array
    {
        /** @var CacheServiceInterface $cacheService */
        $cacheService = App::make(CacheServiceInterface::class);

        // Get the DungeonData
        $dungeonData = $cacheService->remember(sprintf('dungeon_%d_%d', $this->floor->dungeon->id, $this->mappingVersion->id), function () {
            $dungeon = $this->floor->dungeon->load(['enemies', 'enemypacks', 'enemypatrols', 'mapicons', 'mountableareas']);

            // Bit of a loss why the [0] is needed - was introduced after including the without() function
            return array_merge(($this->floor->dungeon()->without(['mapicons', 'enemypacks'])->get()->toArray())[0], $this->getEnemies(), [
                'latestMappingVersion'      => $dungeon->getCurrentMappingVersion(),
                'enemies'                   => $this->mappingVersion->enemies()->without(['npc'])->get()->makeHidden(['enemyactiveauras']),
                'npcs'                      => $dungeon->npcs()->with(['spells'])->get(),
                'auras'                     => Spell::where('aura', true)->get(),
                'enemyPacks'                => $this->mappingVersion->enemyPacks()->with(['enemies:enemies.id,enemies.enemy_pack_id'])->get(),
                'enemyPatrols'              => $this->mappingVersion->enemyPatrols,
                'mapIcons'                  => $this->mappingVersion->mapIcons,
                'dungeonFloorSwitchMarkers' => $this->mappingVersion->dungeonFloorSwitchMarkers,
                'mountableAreas'            => $this->mappingVersion->mountableAreas,
            ]);
        }, config('keystoneguru.cache.dungeonData.ttl'));

        $static = $cacheService->remember('static_data', function () {
            return [
                'mapIconTypes'                      => MapIconType::all(),
                'unknownMapIconType'                => MapIconType::find(MapIconType::ALL[MapIconType::MAP_ICON_TYPE_UNKNOWN]),
                'awakenedObeliskGatewayMapIconType' => MapIconType::find(MapIconType::ALL[MapIconType::MAP_ICON_TYPE_GATEWAY]),
                'classColors'                       => CharacterClass::all()->pluck('color'),
                'raidMarkers'                       => RaidMarker::all(),
                'factions'                          => Faction::where('name', '<>', 'Unspecified')->with('iconfile')->get(),
                'publishStates'                     => PublishedState::all(),
            ];
        }, config('keystoneguru.cache.static_data.ttl'));

        $npcMinHealth = $this->floor->dungeon->getNpcsMinHealth();
        $npcMaxHealth = $this->floor->dungeon->getNpcsMaxHealth();

        // Prevent the values being exactly the same, which causes issues in the front end
        if ($npcMaxHealth <= $npcMinHealth) {
            $npcMaxHealth = $npcMinHealth + 1;
        }

        return [
            'type'                => $this->getType(),
            'mappingVersion'      => $this->mappingVersion,
            'floorId'             => $this->floor->id,
            'teeming'             => $this->isTeeming(),
            'seasonalIndex'       => $this->getSeasonalIndex(),
            'dungeon'             => $dungeonData,
            'static'              => $static,
            'minEnemySizeDefault' => config('keystoneguru.min_enemy_size_default'),
            'maxEnemySizeDefault' => config('keystoneguru.max_enemy_size_default'),
            'npcsMinHealth'       => $npcMinHealth,
            'npcsMaxHealth'       => $npcMaxHealth,

            'keystoneScalingFactor' => config('keystoneguru.keystone.scaling_factor'),

            'echoChannelName' => $this->getEchoChannelName(),
            // May be null
            'userPublicKey'   => optional(Auth::user())->public_key,
        ];
    }
}
