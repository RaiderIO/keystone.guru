<?php


namespace App\Service\Cache;

interface CacheServiceInterface
{
    public function getOtherwiseSet(string $key, $closure, $ttl = null);

    public function get(string $key);

    public function set(string $key, $object, $ttl = null): bool;

    public function has(string $key): bool;

    public function dropCaches(): bool;
}