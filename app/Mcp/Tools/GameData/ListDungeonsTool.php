<?php

namespace App\Mcp\Tools\GameData;

use App\Mcp\Tools\McpTool;
use App\Models\Dungeon;
use App\Models\Season;
use App\Models\User;
use App\Service\Season\SeasonServiceInterface;
use App\Service\User\ApiTokenScope;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Description('Lists the dungeons of the current Mythic+ season (or every active dungeon), with the enemy forces each one requires, its timer and its floors. Use the dungeon key in the other tools.')]
#[IsReadOnly]
#[IsIdempotent]
class ListDungeonsTool extends McpTool
{
    protected string $name = 'list_dungeons';

    public function __construct(
        private readonly SeasonServiceInterface $seasonService,
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
        ]);

        $currentSeason = $this->seasonService->getCurrentSeason();

        $dungeons = Dungeon::query()
            ->select('dungeons.*')
            ->join('expansions', 'dungeons.expansion_id', '=', 'expansions.id')
            ->where('expansions.active', true)
            ->where('dungeons.active', true)
            ->with(['expansion', 'floors'])
            ->orderBy('dungeons.id')
            ->get();

        $currentSeasonDungeonIds = $currentSeason?->dungeons()->pluck('dungeons.id')->all() ?? [];

        if (!($validated['include_all_active'] ?? false)) {
            $dungeons = $dungeons->filter(static fn(Dungeon $dungeon) => in_array($dungeon->id, $currentSeasonDungeonIds, true));
        }

        return Response::structured([
            'current_season' => $currentSeason === null ? null : $this->formatSeason($currentSeason),
            'dungeons'       => $dungeons->map(fn(Dungeon $dungeon) => $this->formatDungeon(
                $dungeon,
                in_array($dungeon->id, $currentSeasonDungeonIds, true),
            ))->values()->all(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function formatSeason(Season $season): array
    {
        return [
            'id'             => $season->id,
            'name'           => $season->name_long,
            'expansion'      => $season->expansion->key,
            'start'          => $season->start->toDateString(),
            'key_level_min'  => $season->key_level_min,
            'key_level_max'  => $season->key_level_max,
            'item_level_min' => $season->item_level_min,
            'item_level_max' => $season->item_level_max,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function formatDungeon(Dungeon $dungeon, bool $inCurrentSeason): array
    {
        $mappingVersion = $dungeon->getCurrentMappingVersion();

        return [
            'key'                           => $dungeon->key,
            'slug'                          => $dungeon->slug,
            'name'                          => __($dungeon->name),
            'abbreviation'                  => $dungeon->abbreviation,
            'expansion'                     => $dungeon->expansion->key,
            'raid'                          => $dungeon->raid,
            'in_current_season'             => $inCurrentSeason,
            'mapping_version'               => $mappingVersion?->version,
            'enemy_forces_required'         => $mappingVersion?->enemy_forces_required,
            'enemy_forces_required_teeming' => $mappingVersion?->enemy_forces_required_teeming,
            'timer_max_seconds'             => $mappingVersion?->timer_max_seconds,
            'floors'                        => $dungeon->floors
                ->where('active', true)
                ->where('facade', false)
                ->map(static fn($floor) => [
                    'id'      => $floor->id,
                    'index'   => $floor->index,
                    'name'    => __($floor->name),
                    'default' => $floor->default,
                ])->values()->all(),
        ];
    }
}
