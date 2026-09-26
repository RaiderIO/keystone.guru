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
use App\Models\Spell\SpellDispelType;
use App\Models\Spell\SpellMissType;
use App\Models\Spell\SpellSchool;
use App\Service\Cache\CacheServiceInterface;
use App\Service\Cache\Traits\RemembersToFile;
use App\Service\WagoTools\GameLocale;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Facades\DB;
use Psr\SimpleCache\InvalidArgumentException;

/**
 * @implements Arrayable<string, mixed>
 */
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
        $staticKey = sprintf('static_data_%s', $this->locale);

        $static = $this->rememberLocal($staticKey, 86400, function () use (
            $staticKey,
        ) {
            $gameLocale = GameLocale::forAppLocale($this->locale);

            $selectableSpells = Spell::where('selectable', true)
                ->when($gameLocale !== GameLocale::English, static fn(Builder $query) => $query->with([
                    'descriptionTranslations' => static fn(Relation $relation) => $relation->where('locale', $gameLocale->value),
                ]))
                ->selectRaw('spells.*, translations.translation as name')
                ->leftJoin('translations', function (JoinClause $clause) {
                    $clause->on('translations.key', '=', 'spells.name')
                        ->on('translations.locale', '=', DB::raw(sprintf('"%s"', $this->locale)));
                })
                ->get()
                ->makeHidden([
                    'debuff',
                    'selectable',
                    'fetched_data_at',
                    'tooltip_data',
                ])
                // The `tooltip_data` accessor resolves through the application locale; this payload is
                // built for `$this->locale`, which `make:mapcontextstatic` varies within one process
                ->map(fn(Spell $spell): array => array_merge($spell->toArray(), [
                    'tooltip_data' => $spell->getTooltipData($this->locale),
                ]))
                ->all();

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
                'factions'                          => Faction::where('key', '<>', Faction::FACTION_UNSPECIFIED)->get(),
                'publishStates'                     => PublishedState::all(),
                'gameVersions'                      => GameVersion::all(),
                'selectableSpells'                  => $selectableSpells,
                'spellSchools'                      => SpellSchool::slugsByBit(),
                'spellMissTypes'                    => SpellMissType::slugsByBit(),
                'spellDispelTypes'                  => SpellDispelType::values(),
            ], config('keystoneguru.cache.static_data.ttl'));
        });

        return [
            'static' => $static,
        ];
    }
}
