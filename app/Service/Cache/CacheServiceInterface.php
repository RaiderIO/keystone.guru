<?php


namespace App\Service\Cache;

interface CacheServiceInterface
{
    public function setCacheEnabled(bool $cacheEnabled): self;

    public function rememberWhen(bool $condition, string $key, $value, ?string $ttl = null);

    public function remember(string $key, $value, ?string $ttl = null);

    public function get(string $key);

    public function set(string $key, $object, ?string $ttl = null): bool;

    public function has(string $key): bool;

    public function dropCaches(): void;
}
