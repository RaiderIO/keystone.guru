<?php


namespace App\Service\DungeonRoute;

use App\Models\Expansion;
use App\Service\Cache\CacheService;
use App\Service\Expansion\ExpansionService;
use App\Service\Season\SeasonService;
use Closure;
use Illuminate\Support\Facades\App;

abstract class BaseDiscoverService implements DiscoverServiceInterface
{
    /** @var CacheService */
    protected CacheService $cacheService;

    /** @var SeasonService */
    protected SeasonService $seasonService;

    /** @var Closure|null */
    protected ?Closure $closure = null;

    /** @var Expansion|null */
    protected Expansion $expansion;

    /**
     * DiscoverService constructor.
     */
    public function __construct()
    {
        $this->cacheService  = App::make(CacheService::class);
        $this->seasonService = App::make(SeasonService::class);

        $expansionService = App::make(ExpansionService::class);
        $this->expansion  = $expansionService->getCurrentExpansion();
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
    function withExpansion(Expansion $expansion): DiscoverServiceInterface
    {
        $this->expansion = $expansion;

        return $this;
    }
}
