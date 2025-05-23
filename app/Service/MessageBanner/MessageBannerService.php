<?php

namespace App\Service\MessageBanner;

use App\Service\Cache\CacheServiceInterface;

readonly class MessageBannerService implements MessageBannerServiceInterface
{
    public function __construct(private CacheServiceInterface $cacheService)
    {
    }

    /**
     * {@inheritDoc}
     */
    public function setMessage(?string $message): bool
    {
        return $this->cacheService->set('message_banner', $message);
    }

    /**
     * {@inheritDoc}
     */
    public function getMessage(): ?string
    {
        return $this->cacheService->get('message_banner');
    }
}
