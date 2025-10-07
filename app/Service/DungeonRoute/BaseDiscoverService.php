<?php

namespace App\Service\DungeonRoute;

use App\Models\Expansion;
use App\Models\GameVersion\GameVersion;
use App\Models\Season;
use App\Service\Cache\CacheServiceInterface;
use App\Service\Expansion\ExpansionService;
use Closure;
use Illuminate\Support\Facades\App;

abstract class BaseDiscoverService implements DiscoverServiceInterface
{
    protected CacheServiceInterface $cacheService;

    protected ExpansionService $expansionService;

    protected int $limit = 10;

    protected ?Closure $closure = null;

    protected ?Season $season = null;

    protected ?GameVersion $gameVersion = null;

    /** @var Expansion|null Only used when browsing by expansion explicitly - do not use otherwise */
    protected ?Expansion $expansion = null;

    /**
     * DiscoverService constructor.
     */
    public function __construct()
    {
        $this->cacheService     = App::make(CacheServiceInterface::class);
        $this->expansionService = App::make(ExpansionService::class);
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
}
