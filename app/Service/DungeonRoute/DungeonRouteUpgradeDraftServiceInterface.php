<?php

namespace App\Service\DungeonRoute;

use App\Models\DungeonRoute\DungeonRoute;
use App\Service\DungeonRoute\Exceptions\UpgradeDraftException;
use Throwable;

/**
 * Drives the draft-and-apply flow for upgrading a dungeon route to a newer mapping version.
 *
 * Pressing Upgrade creates a draft clone linked to the original and upgrades the *draft*, so the
 * original keeps serving its old, intact content while the author repairs the draft. Apply then
 * replaces the original's contents with the draft's, preserving the original's id and public key so
 * every inbound reference (Raider.IO, embeds, favorites, ratings, pageviews, metrics, MDT imports,
 * challenge mode runs) survives.
 */
interface DungeonRouteUpgradeDraftServiceInterface
{
    /**
     * Returns the existing upgrade draft of $original, or creates one and upgrades it to the
     * dungeon's current mapping version.
     *
     * @throws UpgradeDraftException When $original is itself a draft, or is a sandbox route.
     * @throws Throwable
     */
    public function findOrCreateDraft(DungeonRoute $original): DungeonRoute;

    /**
     * Replaces the contents and settings of the draft's original with the draft's, preserving the
     * original's identity, and deletes the draft.
     *
     * @return DungeonRoute The original, refreshed.
     *
     * @throws UpgradeDraftException When $draft is not a draft, or its original no longer exists.
     * @throws Throwable
     */
    public function apply(DungeonRoute $draft): DungeonRoute;

    /**
     * Deletes the draft, leaving its original untouched.
     *
     * @throws UpgradeDraftException When $draft is not a draft.
     * @throws Throwable
     */
    public function discard(DungeonRoute $draft): void;
}
