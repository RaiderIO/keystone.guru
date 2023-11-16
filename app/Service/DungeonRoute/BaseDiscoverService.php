<?php


namespace App\Service\DungeonRoute;

use App\Models\Expansion;
use App\Models\GameServerRegion;
use App\Models\Season;
use App\Service\Cache\CacheServiceInterface;
use App\Service\Expansion\ExpansionService;
use Closure;
use Illuminate\Support\Facades\App;

abstract class BaseDiscoverService implements DiscoverServiceInterface
{
    /** @var CacheServiceInterface */
    protected CacheServiceInterface $cacheService;

    /** @var ExpansionService */
    protected ExpansionService $expansionService;

    /** @var int */
    protected int $limit = 10;

    /** @var Closure|null */
    protected ?Closure $closure = null;

    /** @var Season|null */
    protected ?Season $season = null;

    /** @var Expansion|null */
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
     * @return DiscoverServiceInterface
     */
    function ensureExpansion(): DiscoverServiceInterface
    {
        if ($this->expansion === null) {
            $this->expansion = $this->expansionService->getCurrentExpansion(GameServerRegion::getUserOrDefaultRegion());
        }

        return $this;
    }

    /**
     * @inheritDoc
     */
    function withLimit(int $limit): DiscoverServiceInterface
    {
        $this->limit = $limit;

        return $this;
    }

    /**
     * @inheritDoc
     */
    function withBuilder(Closure $closure): DiscoverServiceInterface
    {
        $this->closure = $closure;

        return $this;
    }

    /**
     * @inheritDoc
     */
    function withSeason(?Season $season): DiscoverServiceInterface
    {
        $this->season = $season;

        return $this;
    }

    /**
     * @inheritDoc
     */
    function withExpansion(Expansion $expansion): DiscoverServiceInterface
    {
        $this->expansion = $expansion;

        return $this;
    }

    /**
     * @inheritDoc
     */
    function withCache(bool $enabled): DiscoverServiceInterface
    {
        $this->cacheService->setCacheEnabled($enabled);

        return $this;
    }
}
