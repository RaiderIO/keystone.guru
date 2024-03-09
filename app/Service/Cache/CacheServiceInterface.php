<?php

namespace App\Service\Cache;

interface CacheServiceInterface
{
    public function setCacheEnabled(bool $cacheEnabled): self;

    public function rememberWhen(bool $condition, string $key, mixed $value, mixed $ttl = null): mixed;

    public function remember(string $key, mixed $value, mixed $ttl = null): mixed;

    public function get(string $key): mixed;

    public function set(string $key, mixed $object, mixed $ttl = null): bool;

    public function has(string $key): bool;

    public function dropCaches(): void;

    public function clearIdleKeys(?int $seconds = null): int;
}
