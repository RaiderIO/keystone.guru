<?php

namespace App\Mcp\Tools\GameData;

use App\Mcp\Tools\McpTool;
use App\Models\Dungeon;
use App\Models\DungeonDifficulty;
use App\Models\GameServerRegion;
use App\Models\GameVersion\GameVersion;
use App\Models\Season;
use App\Models\User;
use App\Repositories\Interfaces\DungeonRepositoryInterface;
use App\Repositories\Interfaces\GameVersion\GameVersionRepositoryInterface;
use App\Repositories\Interfaces\Mapping\MappingVersionRepositoryInterface;
use App\Service\Season\SeasonServiceInterface;
use App\Service\User\ApiTokenScope;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Validation\Rule;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Description('Lists the dungeons of the current Mythic+ season (or every active dungeon of a game version, optionally of one expansion), with the enemy forces each one requires, its timer, its floors, the game versions it is mapped for and its speedrun difficulties. Listing every active dungeon of every expansion leaves the floors out; filter on an expansion for them. The season start is the reset of your keystone.guru region. Use the dungeon key in the other tools.')]
#[IsReadOnly]
#[IsIdempotent]
class ListDungeonsTool extends McpTool
{
    protected string $name = 'list_dungeons';

    public function __construct(
        private readonly SeasonServiceInterface            $seasonService,
        private readonly DungeonRepositoryInterface        $dungeonRepository,
        private readonly MappingVersionRepositoryInterface $mappingVersionRepository,
        private readonly GameVersionRepositoryInterface    $gameVersionRepository,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'include_all_active' => $schema->boolean()
                ->description('List every active dungeon instead of only those of the current season. Defaults to false.'),
            'expansion' => $schema->string()
                ->description('Only list the dungeons of this expansion key, e.g. "midnight" (the expansion field of a dungeon).'),
            'game_version' => $schema->string()->enum(array_keys(GameVersion::ALL))
                ->description('With include_all_active: only list dungeons mapped for this game version. Defaults to your keystone.guru game version; the current season is always retail.'),
        ];
    }

    protected function requiredScope(): ApiTokenScope
    {
        return ApiTokenScope::RoutesRead;
    }

    protected function run(Request $request, User $user): Response|ResponseFactory
    {
        $validated = $request->validate([
            'include_all_active' => ['nullable', 'boolean'],
            'expansion'          => ['nullable', 'string', 'max:255'],
            'game_version'       => ['nullable', 'string', Rule::in(array_keys(GameVersion::ALL))],
        ]);

        $includeAllActive = (bool)($validated['include_all_active'] ?? false);
        $expansionKey     = $validated['expansion'] ?? null;
        $region           = GameServerRegion::getUserOrDefaultRegion();

        $currentSeason           = $this->seasonService->getCurrentSeason(null, $region);
        $currentSeasonDungeonIds = $currentSeason?->dungeons()->pluck('dungeons.id')->all() ?? [];

        $gameVersion = null;
        if ($includeAllActive) {
            $gameVersion = isset($validated['game_version'])
                ? $this->gameVersionRepository->findByKey($validated['game_version'])
                : $user->gameVersion;
        }

        $dungeons = $this->dungeonRepository->getActiveInActiveExpansions($expansionKey, $gameVersion);
        if (!$includeAllActive) {
            $dungeons = $dungeons->filter(static fn(Dungeon $dungeon) => in_array($dungeon->id, $currentSeasonDungeonIds, true));
        }

        $gameVersionKeysByDungeonId = $this->mappingVersionRepository->getGameVersionKeysByDungeonId($dungeons->pluck('id'));

        return Response::structured(array_filter([
            'region'         => $region->key,
            'game_version'   => $gameVersion?->key,
            'current_season' => $currentSeason === null ? null : $this->formatSeason($currentSeason, $region),
            'dungeons'       => $dungeons->map(fn(Dungeon $dungeon) => $this->formatDungeon(
                $dungeon,
                in_array($dungeon->id, $currentSeasonDungeonIds, true),
                $gameVersionKeysByDungeonId->get($dungeon->id, []),
                // Every floor of every active dungeon is far more than an AI client can take in one response
                !$includeAllActive || $expansionKey !== null,
            ))->values()->all(),
        ], static fn($value) => $value !== null));
    }

    /**
     * @return array<string, mixed>
     */
    private function formatSeason(Season $season, GameServerRegion $region): array
    {
        return [
            'id'             => $season->id,
            'name'           => $season->name_long,
            'expansion'      => $season->expansion->key,
            'start'          => $season->start($region)->toIso8601String(),
            'key_level_min'  => $season->key_level_min,
            'key_level_max'  => $season->key_level_max,
            'item_level_min' => $season->item_level_min,
            'item_level_max' => $season->item_level_max,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    /**
     * @param  array<int, string>   $gameVersionKeys
     * @return array<string, mixed>
     */
    private function formatDungeon(Dungeon $dungeon, bool $inCurrentSeason, array $gameVersionKeys, bool $includeFloors): array
    {
        $mappingVersion = $dungeon->getCurrentMappingVersion();

        $result = [
            'key'                           => $dungeon->key,
            'slug'                          => $dungeon->slug,
            'name'                          => __($dungeon->name),
            'abbreviation'                  => __($dungeon->abbreviation),
            'expansion'                     => $dungeon->expansion->key,
            'raid'                          => $dungeon->raid,
            'in_current_season'             => $inCurrentSeason,
            'mapping_version'               => $mappingVersion?->version,
            'enemy_forces_required'         => $mappingVersion?->enemy_forces_required,
            'enemy_forces_required_teeming' => $mappingVersion?->enemy_forces_required_teeming,
            'timer_max_seconds'             => $mappingVersion?->timer_max_seconds,
            'game_versions'                 => $gameVersionKeys,
        ];

        $difficulties = $dungeon->speedrun_enabled ? $dungeon->getEnabledDungeonDifficulties() : [];
        if ($difficulties !== []) {
            $result['difficulties'] = array_map(static fn(DungeonDifficulty $difficulty) => [
                'id'   => $difficulty->value,
                'name' => $difficulty->translatedName(),
            ], $difficulties);
        }

        if ($includeFloors) {
            $result['floors'] = $dungeon->floors
                ->where('active', true)
                ->where('facade', false)
                ->map(static fn($floor) => [
                    'id'      => $floor->id,
                    'index'   => $floor->index,
                    'name'    => __($floor->name),
                    'default' => $floor->default,
                ])->values()->all();
        }

        return $result;
    }
}
