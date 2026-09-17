<?php

namespace App\Service\Cache;

interface CacheServiceInterface
{
    public function isCacheEnabled(): bool;

    public function setCacheEnabled(bool $cacheEnabled): self;

    public function rememberWhen(bool $condition, string $key, mixed $value, mixed $ttl = null): mixed;

    public function remember(string $key, mixed $value, mixed $ttl = null): mixed;

    /**
     * Remembers a value in a single Redis hash, keyed by field. All fields of a hash can then be dropped in one
     * operation with {@see self::dropHashCache()}, regardless of how many fields exist.
     *
     * @param  \Closure|mixed                $value
     * @param  bool|array<int, class-string> $allowedClasses The classes the cached value may be unserialized
     *                                                       into: false refuses every class, true allows all,
     *                                                       an array allows only the class names it lists.
     * @return \Closure|mixed|null
     */
    public function rememberInHash(string $hashKey, string $field, mixed $value, mixed $ttl = null, bool|array $allowedClasses = false): mixed;

    /**
     * Drops an entire Redis hash (and all its fields) in a single operation.
     */
    public function dropHashCache(string $hashKey): void;

    public function get(string $key): mixed;

    public function set(string $key, mixed $object, mixed $ttl = null): bool;

    public function has(string $key): bool;

    public function dropCaches(): void;

    public function clearIdleKeys(): int;

    public function lock(string $key, callable $callable, int $waitFor = 60): mixed;
}
