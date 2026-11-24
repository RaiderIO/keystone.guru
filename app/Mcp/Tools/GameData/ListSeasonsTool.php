<?php

namespace App\Mcp\Tools\GameData;

use App\Mcp\Tools\McpTool;
use App\Models\Affix;
use App\Models\AffixGroup\AffixGroup;
use App\Models\Dungeon;
use App\Models\GameServerRegion;
use App\Models\Season;
use App\Models\User;
use App\Repositories\Interfaces\AffixRepositoryInterface;
use App\Repositories\Interfaces\DungeonRepositoryInterface;
use App\Repositories\Interfaces\DungeonRoute\DungeonRouteRepositoryInterface;
use App\Repositories\Interfaces\SeasonRepositoryInterface;
use App\Service\Season\SeasonServiceInterface;
use App\Service\User\ApiTokenScope;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Description('Lists the Mythic+ seasons of the active expansions with their key and item level ranges, dungeons, seasonal affix and affix groups; is_current marks the current one. list_affixes describes each affix.')]
#[IsReadOnly]
#[IsIdempotent]
class ListSeasonsTool extends McpTool
{
    protected string $name = 'list_seasons';

    public function __construct(
        private readonly SeasonServiceInterface    $seasonService,
        private readonly SeasonRepositoryInterface $seasonRepository,
        private readonly AffixRepositoryInterface  $affixRepository,
        DungeonRouteRepositoryInterface            $dungeonRouteRepository,
        DungeonRepositoryInterface                 $dungeonRepository,
    ) {
        parent::__construct($dungeonRouteRepository, $dungeonRepository);
    }

    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [];
    }

    protected function requiredScope(): ApiTokenScope
    {
        return ApiTokenScope::RoutesRead;
    }

    protected function run(Request $request, User $user): Response|ResponseFactory
    {
        $region          = GameServerRegion::getUserOrDefaultRegion();
        $currentSeasonId = $this->seasonService->getCurrentSeason(null, $region)?->id;

        $seasons = $this->seasonRepository->getInActiveExpansionsWithDungeonsAndAffixGroups();

        $seasonalAffixKeys = $this->affixRepository->getKeysByIds($seasons->pluck('seasonal_affix_id')->filter());

        return Response::structured([
            'region'  => $region->key,
            'seasons' => $seasons->map(static fn(Season $season) => [
                'id'             => $season->id,
                'name'           => $season->name_long,
                'expansion'      => $season->expansion->key,
                'is_current'     => $season->id === $currentSeasonId,
                'start'          => $season->start($region)->toIso8601String(),
                'key_level_min'  => $season->key_level_min,
                'key_level_max'  => $season->key_level_max,
                'item_level_min' => $season->item_level_min,
                'item_level_max' => $season->item_level_max,
                'seasonal_affix' => $season->seasonal_affix_id === null ? null : $seasonalAffixKeys->get($season->seasonal_affix_id),
                'dungeons'       => $season->dungeons->map(static fn(Dungeon $dungeon) => $dungeon->key)->values()->all(),
                'affix_groups'   => $season->affixGroups->map(static fn(AffixGroup $affixGroup) => [
                    'id'             => $affixGroup->id,
                    'seasonal_index' => $affixGroup->seasonal_index,
                    'affixes'        => $affixGroup->affixes->map(static fn(Affix $affix) => $affix->key)->values()->all(),
                ])->values()->all(),
            ])->values()->all(),
        ]);
    }
}
