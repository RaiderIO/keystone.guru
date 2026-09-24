<?php

namespace App\Service\DungeonRoute;

use App\Service\Cache\CacheServiceInterface;

class ThumbnailGenerationToggleService implements ThumbnailGenerationToggleServiceInterface
{
    private const string CACHE_KEY = 'thumbnail_generation_paused';

    public function __construct(private readonly CacheServiceInterface $cacheService)
    {
    }

    /**
     * {@inheritDoc}
     */
    public function setPaused(bool $paused): bool
    {
        return $this->cacheService->set(self::CACHE_KEY, $paused);
    }

    /**
     * {@inheritDoc}
     */
    public function isPaused(): bool
    {
        return (bool)$this->cacheService->get(self::CACHE_KEY) === true;
    }
}
