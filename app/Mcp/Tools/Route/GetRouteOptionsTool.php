<?php

namespace App\Mcp\Tools\Route;

use App\Logic\Structs\LatLng;
use App\Mcp\Tools\McpTool;
use App\Models\Affix;
use App\Models\AffixGroup\AffixGroup;
use App\Models\CharacterClass;
use App\Models\CharacterClassSpecialization;
use App\Models\CharacterRace;
use App\Models\DungeonStart;
use App\Models\Faction;
use App\Models\GameServerRegion;
use App\Models\RouteAttribute;
use App\Models\Spell\Spell;
use App\Models\User;
use App\Repositories\Interfaces\CharacterClassRepositoryInterface;
use App\Repositories\Interfaces\CharacterRaceRepositoryInterface;
use App\Repositories\Interfaces\DungeonRepositoryInterface;
use App\Repositories\Interfaces\DungeonRoute\DungeonRouteRepositoryInterface;
use App\Repositories\Interfaces\FactionRepositoryInterface;
use App\Repositories\Interfaces\RouteAttributeRepositoryInterface;
use App\Repositories\Interfaces\SpellRepositoryInterface;
use App\Service\Coordinates\CoordinatesServiceInterface;
use App\Service\Season\SeasonServiceInterface;
use App\Service\User\ApiTokenScope;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Description('Returns the ids a route can be set up with: factions, classes with their specializations, races, route attributes, the spells a pull can have (Bloodlust, cooldowns), and the current season\'s affix groups. With a dungeon, also its dungeon starts (in-game coordinates) and speedrun difficulties.')]
#[IsReadOnly]
#[IsIdempotent]
class GetRouteOptionsTool extends McpTool
{
    protected string $name = 'get_route_options';

    public function __construct(
        private readonly SeasonServiceInterface            $seasonService,
        private readonly CoordinatesServiceInterface       $coordinatesService,
        private readonly FactionRepositoryInterface        $factionRepository,
        private readonly CharacterClassRepositoryInterface $characterClassRepository,
        private readonly CharacterRaceRepositoryInterface  $characterRaceRepository,
        private readonly RouteAttributeRepositoryInterface $routeAttributeRepository,
        private readonly SpellRepositoryInterface          $spellRepository,
        DungeonRouteRepositoryInterface                    $dungeonRouteRepository,
        DungeonRepositoryInterface                         $dungeonRepository,
    ) {
        parent::__construct($dungeonRouteRepository, $dungeonRepository);
    }

    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'dungeon' => $schema->string()->description('A dungeon key or slug, for its dungeon starts and speedrun difficulties.'),
        ];
    }

    protected function requiredScope(): ApiTokenScope
    {
        return ApiTokenScope::RoutesRead;
    }

    protected function run(Request $request, User $user): Response|ResponseFactory
    {
        $validated = $request->validate([
            'dungeon' => ['nullable', 'string', 'max:255'],
        ]);

        $currentSeason = $this->seasonService->getCurrentSeason(null, GameServerRegion::getUserOrDefaultRegion($user));

        $result = [
            'factions' => $this->factionRepository->getAllOrderedById()->map(static fn(Faction $faction) => [
                'id'   => $faction->id,
                'key'  => $faction->key,
                'name' => __($faction->name),
            ])->values()->all(),
            'classes' => $this->characterClassRepository->getAllWithSpecializationsOrderedById()->map(static fn(CharacterClass $characterClass) => [
                'id'              => $characterClass->id,
                'key'             => $characterClass->key,
                'name'            => __($characterClass->name),
                'specializations' => $characterClass->specializations->map(static fn(CharacterClassSpecialization $specialization) => [
                    'id'   => $specialization->id,
                    'key'  => $specialization->key,
                    'name' => __($specialization->name),
                ])->values()->all(),
            ])->values()->all(),
            'races' => $this->characterRaceRepository->getAllWithFactionOrderedById()->map(static fn(CharacterRace $characterRace) => [
                'id'      => $characterRace->id,
                'key'     => $characterRace->key,
                'name'    => __($characterRace->name),
                'faction' => $characterRace->faction->key,
            ])->values()->all(),
            'route_attributes' => $this->routeAttributeRepository->getAllOrderedById()->map(static fn(RouteAttribute $routeAttribute) => [
                'id'       => $routeAttribute->id,
                'key'      => $routeAttribute->key,
                'category' => $routeAttribute->category,
                'name'     => __($routeAttribute->name),
            ])->values()->all(),
            'pull_spells' => $this->spellRepository->getSelectable()->map(static fn(Spell $spell) => [
                'id'       => $spell->id,
                'name'     => __($spell->name ?? ''),
                'category' => $spell->category === null ? null : __($spell->category),
            ])->values()->all(),
            'current_season_affix_groups' => $currentSeason?->affixGroups()->with('affixes')->orderBy('id')->get()->map(static fn(AffixGroup $affixGroup) => [
                'id'             => $affixGroup->id,
                'seasonal_index' => $affixGroup->seasonal_index,
                'affixes'        => $affixGroup->affixes->map(static fn(Affix $affix) => $affix->key)->values()->all(),
            ])->values()->all() ?? [],
        ];

        if (isset($validated['dungeon'])) {
            $dungeon        = $this->findActiveDungeon($validated['dungeon']);
            $mappingVersion = $dungeon->getCurrentMappingVersion();

            $result['dungeon_starts'] = $mappingVersion?->dungeonStarts()->with('floor')->orderBy('id')->get()->map(function (DungeonStart $dungeonStart) {
                $ingameXY = $this->coordinatesService->calculateIngameLocationForMapLocation(
                    new LatLng($dungeonStart->lat, $dungeonStart->lng, $dungeonStart->floor),
                );

                return [
                    'id'       => $dungeonStart->id,
                    'floor_id' => $dungeonStart->floor_id,
                    'x'        => round($ingameXY->getX(), 1),
                    'y'        => round($ingameXY->getY(), 1),
                ];
            })->values()->all() ?? [];
            $result['speedrun_difficulties'] = $dungeon->speedrun_enabled ? $dungeon->getEnabledSpeedrunDifficulties() : [];
        }

        return Response::structured($result);
    }
}
