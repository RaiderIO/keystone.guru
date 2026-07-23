<?php

namespace App\Http\View\Composers;

use App\Models\AffixGroup\AffixGroup;
use App\Models\AffixGroup\AffixGroupEaseTier;
use App\Models\GameVersion\GameVersion;
use App\Service\AffixGroup\AffixGroupEaseTierServiceInterface;
use App\Service\Cache\CacheServiceInterface;
use App\Service\Dungeon\DungeonServiceInterface;
use App\Service\Season\SeasonAffixGroupServiceInterface;
use App\Service\View\RequestViewContextInterface;
use App\Service\View\ViewServiceInterface;
use Illuminate\Support\Collection;
use Illuminate\View\View;

readonly class HeaderComposer implements ViewComposerInterface
{
    public function __construct(
        private ViewServiceInterface               $viewService,
        private RequestViewContextInterface        $requestViewContext,
        private DungeonServiceInterface            $dungeonService,
        private SeasonAffixGroupServiceInterface   $seasonAffixGroupService,
        private AffixGroupEaseTierServiceInterface $affixGroupEaseTierService,
        private CacheServiceInterface              $cacheService,
    ) {
    }

    public function compose(View $view): void
    {
        $gameServerRegion = $this->requestViewContext->getUserOrDefaultRegion();

        $currentSeason = $this->viewService->getCurrentSeasonForRegion($gameServerRegion);

        $view->with('activeExpansions', $this->viewService->getActiveExpansions());
        $view->with('currentSeason', $currentSeason);
        $view->with('nextSeason', $this->viewService->getNextSeasonForRegion($gameServerRegion));
        $view->with('allGameVersions', $this->viewService->getAllGameVersions());

        $userOrDefaultGameVersion = GameVersion::getUserOrDefaultGameVersion();
        $view->with('gameVersionDungeons', $this->dungeonService->getDungeonsForGameVersion($userOrDefaultGameVersion));

        // Ease tiers for the dungeon-context strip ("what's easy this week", archon.gg data). The header
        // renders on every page, so the current affix group + tier lookup are resolved once and cached
        // here - the underlying data only changes ~weekly.
        $currentAffixGroup = $currentSeason === null ? null : $this->seasonAffixGroupService->getCurrentAffixGroupInRegion($currentSeason, $gameServerRegion);

        $view->with('dungeonContextCurrentAffixGroup', $currentAffixGroup);
        $view->with('dungeonContextEaseTiers', $this->getEaseTiers($currentAffixGroup));
    }

    /**
     * The ease tier letter per dungeon for the given affix group, shaped for a cheap per-dungeon lookup
     * in the dungeon-context cards: `[affixGroupId => [dungeonId => 'S']]`.
     *
     * @return Collection<int, Collection<int, string>>
     */
    private function getEaseTiers(?AffixGroup $currentAffixGroup): Collection
    {
        if ($currentAffixGroup === null) {
            return collect();
        }

        return $this->cacheService->remember(
            sprintf('header:dungeon_context_ease_tiers:%d', $currentAffixGroup->id),
            function () use ($currentAffixGroup) {
                // getTiersByAffixGroups() groups by affix_group_id despite its flat return type hint.
                /** @var Collection<int, Collection<int, AffixGroupEaseTier>> $grouped */
                $grouped = $this->affixGroupEaseTierService->getTiersByAffixGroups(collect([$currentAffixGroup]));

                return $grouped->map(fn(Collection $tiers) => $tiers->pluck('tier', 'dungeon_id'));
            },
            config('keystoneguru.cache.displayed_affix_groups.ttl'),
        );
    }
}
