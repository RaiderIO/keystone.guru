<?php

namespace App\Service\User;

interface UserSlugServiceInterface
{
    /**
     * The URL-safe slug a username reduces to, before any collision suffix: lowercased, every run of
     * characters other than Unicode letters, digits and underscores collapsed to a single dash.
     * Returns `user` when nothing usable remains.
     */
    public function generateBaseSlug(string $name): string;

    /**
     * The first slug for this username no other user holds: the base slug, else base-2, base-3, ...
     */
    public function findAvailableSlug(string $name, ?int $exceptUserId = null): string;

    /**
     * Whether a user other than $exceptUserId already holds the base slug this username reduces to.
     */
    public function isBaseSlugTaken(string $name, ?int $exceptUserId = null): bool;
}
