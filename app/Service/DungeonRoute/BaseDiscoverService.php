<?php

namespace App\Service\DungeonRoute;

use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Expansion;
use App\Models\GameVersion\GameVersion;
use App\Models\Season;
use App\Models\Team;
use App\Repositories\Database\DungeonRoute\Dtos\WeeklyRoute;
use App\Repositories\Interfaces\DungeonRoute\DungeonRouteRepositoryInterface;
use App\Service\Cache\CacheServiceInterface;
use App\Service\Expansion\ExpansionServiceInterface;
use Closure;
use Illuminate\Support\Collection;

abstract class BaseDiscoverService implements DiscoverServiceInterface
{
    /**
     * The default number of top community routes per dungeon that are promoted into the hero band.
     */
    private const int HERO_TOP_ROUTES_PER_DUNGEON = 3;

    protected int $limit = 10;

    protected ?Closure $closure = null;

    protected ?Season $season = null;

    protected ?GameVersion $gameVersion = null;

    /** @var Expansion|null Only used when browsing by expansion explicitly - do not use otherwise */
    protected ?Expansion $expansion = null;

    protected ?Team $excludeTeam = null;

    /**
     * DiscoverService constructor.
     */
    public function __construct(
        protected CacheServiceInterface                  $cacheService,
        protected ExpansionServiceInterface              $expansionService,
        private readonly DungeonRouteRepositoryInterface $dungeonRouteRepository,
    ) {
    }

    /**
     * Makes sure that we have an expansion set at the end of this function if it wasn't set before
     */
    public function ensureGameVersion(): DiscoverServiceInterface
    {
        if ($this->gameVersion === null) {
            $this->gameVersion = GameVersion::getDefaultGameVersion();
        }

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function withLimit(int $limit): DiscoverServiceInterface
    {
        $this->limit = $limit;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function withBuilder(Closure $closure): DiscoverServiceInterface
    {
        $this->closure = $closure;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function withSeason(?Season $season): DiscoverServiceInterface
    {
        $this->season = $season;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function withExpansion(Expansion $expansion): DiscoverServiceInterface
    {
        $this->expansion = $expansion;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function withGameVersion(GameVersion $gameVersion): DiscoverServiceInterface
    {
        $this->gameVersion = $gameVersion;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function withCache(bool $enabled): DiscoverServiceInterface
    {
        $this->cacheService->setCacheEnabled($enabled);

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function excludeTeam(?Team $team): DiscoverServiceInterface
    {
        $this->excludeTeam = $team;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function heroRoutes(Season $season, int $topPerDungeon = self::HERO_TOP_ROUTES_PER_DUNGEON): Collection
    {
        /** @var Collection<int, DungeonRoute> $heroRoutes */
        $heroRoutes = collect();

        // The Raider.IO weekly routes (grouped by dungeon key) are always shown as heroes.
        $this->dungeonRouteRepository->getWeeklyRoutes()
            ->flatten()
            ->each(function (WeeklyRoute $weeklyRoute) use ($heroRoutes) {
                if ($weeklyRoute->dungeonRoute !== null) {
                    $heroRoutes->push($weeklyRoute->dungeonRoute);
                }
            });

        // The top community routes per dungeon, mirroring the discovery page's popularity ordering.
        $this->excludeTeam(Team::getRaiderIOTeam())
            ->withSeason($season)
            ->withLimit($topPerDungeon);

        foreach ($season->dungeons as $dungeon) {
            $this->popularByDungeon($dungeon)
                ->take($topPerDungeon)
                ->each(fn(DungeonRoute $dungeonRoute) => $heroRoutes->push($dungeonRoute));
        }

        return $heroRoutes->unique('id')->values();
    }
}
