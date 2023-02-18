<?php

namespace App\Service\ReadOnlyMode;

use App\Service\Cache\CacheServiceInterface;

class ReadOnlyModeService implements ReadOnlyModeServiceInterface
{
    private CacheServiceInterface $cacheService;

    /**
     * @param CacheServiceInterface $cacheService
     */
    public function __construct(CacheServiceInterface $cacheService)
    {
        $this->cacheService = $cacheService;
    }


    /**
     * @inheritDoc
     */
    public function setReadOnly(bool $readOnly): bool
    {
        return $this->cacheService->set('read_only_mode', $readOnly);
    }

    /**
     * @inheritDoc
     */
    public function isReadOnly(): bool
    {
        return $this->cacheService->has('read_only_mode') && (bool)$this->cacheService->get('read_only_mode') === true;
    }
}
