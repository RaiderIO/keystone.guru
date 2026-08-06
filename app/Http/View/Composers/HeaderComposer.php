<?php

namespace App\Http\View\Composers;

use App\Models\AffixGroup\AffixGroup;
use App\Models\AffixGroup\AffixGroupEaseTier;
use App\Models\GameVersion\GameVersion;
use App\Models\Season;
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

        // A season is seeded weeks - sometimes months - before it starts so its mapping can be
        // reviewed; until an admin marks it `active` it must not be visible anywhere (#3761, #3868).
        $nextSeason = $this->getVisibleUpcomingSeason($this->viewService->getNextSeasonForRegion($gameServerRegion));

        $view->with('activeExpansions', $this->viewService->getActiveExpansions());
        $view->with('currentSeason', $currentSeason);
        $view->with('nextSeason', $nextSeason);
        $view->with('allGameVersions', $this->viewService->getAllGameVersions());

        $userOrDefaultGameVersion = GameVersion::getUserOrDefaultGameVersion();
        $view->with('gameVersionDungeons', $this->dungeonService->getDungeonsForGameVersion($userOrDefaultGameVersion));

        // The dungeon context bar follows the current season only (#3761) - the upcoming season is
        // advertised next to it as a card of its own, leading to a selection of just its dungeons.
        // Seasons are a retail concept: the dungeon selection hides every season for a game version
        // without them, so advertising one there would lead to a page that cannot show it.
        $upcomingSeason = $userOrDefaultGameVersion->has_seasons ? $nextSeason : null;

        $view->with('dungeonContextNextSeason', $upcomingSeason);
        $view->with('dungeonContextNextSeasonLink', $upcomingSeason === null ? null : route('dungeon.explore.gameversion.select', [
            'gameVersion' => $userOrDefaultGameVersion,
            'season'      => $upcomingSeason->id,
        ]));

        // Ease tiers for the dungeon-context strip ("what's easy this week", archon.gg data). The header
        // renders on every page, so the current affix group + tier lookup are resolved once and cached
        // here - the underlying data only changes ~weekly.
        $currentAffixGroup = $currentSeason === null ? null : $this->seasonAffixGroupService->getCurrentAffixGroupInRegion($currentSeason, $gameServerRegion);

        $view->with('dungeonContextCurrentAffixGroup', $currentAffixGroup);
        $view->with('dungeonContextEaseTiers', $this->getEaseTiers($currentAffixGroup));
    }

    /**
     * The next season, but only once an admin has marked it ready to reveal. A season is seeded weeks -
     * sometimes months - before it starts so its mapping can be reviewed, and until then it should not be
     * visible on the site at all.
     */
    private function getVisibleUpcomingSeason(?Season $nextSeason): ?Season
    {
        return $nextSeason?->active ? $nextSeason : null;
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
