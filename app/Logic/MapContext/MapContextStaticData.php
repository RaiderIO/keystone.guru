<?php

namespace App\Logic\MapContext;

use App\Models\CharacterClass;
use App\Models\CharacterClassSpecialization;
use App\Models\Faction;
use App\Models\GameVersion\GameVersion;
use App\Models\MapIconType;
use App\Models\PublishedState;
use App\Models\RaidMarker;
use App\Models\Spell\Spell;
use App\Service\Cache\CacheServiceInterface;
use App\Service\Cache\Traits\RemembersToFile;
use DragonCode\Contracts\Support\Arrayable;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Facades\DB;
use Psr\SimpleCache\InvalidArgumentException;

class MapContextStaticData implements Arrayable
{
    use RemembersToFile;

    public function __construct(
        protected CacheServiceInterface $cacheService,
        protected string                $locale,
    ) {
    }

    /**
     * @throws InvalidArgumentException
     */
    public function toArray(): array
    {
        $staticKey = 'static_data_%s';
        $static    = $this->rememberLocal($staticKey, 86400, function () use (
            $staticKey,
        ) {
            $selectableSpells = Spell::where('selectable', true)
                ->selectRaw('spells.*, translations.translation as name')
                ->leftJoin('translations', function (JoinClause $clause) {
                    $clause->on('translations.key', 'spells.name')
                        ->on('translations.locale', DB::raw(sprintf('"%s"', $this->locale)));
                })
                ->get();

            $characterClasses = CharacterClass::all();
            $mapIconTypes     = MapIconType::all()->keyBy('id');

            return $this->cacheService->remember($staticKey, static fn() => [
                'mapIconTypes'                      => $mapIconTypes->values(),
                'unknownMapIconType'                => $mapIconTypes->get(MapIconType::ALL[MapIconType::MAP_ICON_TYPE_UNKNOWN]),
                'awakenedObeliskGatewayMapIconType' => $mapIconTypes->get(MapIconType::ALL[MapIconType::MAP_ICON_TYPE_GATEWAY]),
                'classColors'                       => $characterClasses->pluck('color'),
                'characterClasses'                  => $characterClasses,
                'characterClassSpecializations'     => CharacterClassSpecialization::all(),
                'raidMarkers'                       => RaidMarker::all(),
                'factions'                          => Faction::where('name', '<>', 'Unspecified')->with('iconfile')->get(),
                'publishStates'                     => PublishedState::all(),
                'gameVersions'                      => GameVersion::all(),
                'selectableSpells'                  => $selectableSpells,
            ], config('keystoneguru.cache.static_data.ttl'));
        });

        return [
            'static' => $static,
        ];
    }
}
