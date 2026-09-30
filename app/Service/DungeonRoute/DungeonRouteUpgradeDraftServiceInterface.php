<?php

namespace App\Service\DungeonRoute;

use App\Logic\MDT\Exception\ImportWarning;
use App\Logic\MDT\Exception\InvalidMDTDungeonException;
use App\Logic\MDT\Exception\InvalidMDTStringException;
use App\Logic\MDT\Exception\MDTStringParseException;
use App\Models\DungeonRoute\DungeonRoute;
use App\Service\DungeonRoute\Exceptions\PendingUpgradeDraftException;
use App\Service\DungeonRoute\Exceptions\StaleUpgradeDraftException;
use App\Service\DungeonRoute\Exceptions\UpgradeDraftException;
use App\Service\DungeonRoute\Exceptions\UpgradeDraftGoneException;
use Illuminate\Support\Collection;
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
     * Imports an MDT string as the draft of $original, so that applying the draft replaces the original's
     * contents with the string's while the original keeps its identity, audience and metadata.
     *
     * The string is rejected before anything is persisted when its preview has errors, when it is for another
     * dungeon, or when it was built against a mapping version older than the dungeon's newest MDT-synced one.
     * When keystone.guru's current mapping version is newer than that MDT-synced one, the draft is upgraded
     * to it. A pending draft being discarded is only removed once the new draft is fully imported and upgraded,
     * so a failure leaves the pending draft untouched.
     *
     * @param  Collection<int, ImportWarning> $warnings               Receives the import's warnings.
     * @param  int|null                       $discardExistingDraftId The id of the pending draft of $original the author
     *                                                                confirmed may be discarded to make room for this one.
     * @return DungeonRoute                   The new draft.
     *
     * @throws PendingUpgradeDraftException When $original already has a draft and $discardExistingDraftId is null.
     * @throws StaleUpgradeDraftException   When $original's draft is not the one $discardExistingDraftId names: it was
     *                                      replaced since the author confirmed. The new route is removed again.
     * @throws UpgradeDraftException        When $original is a draft or a sandbox route, or the string is rejected.
     * @throws MDTStringParseException
     * @throws InvalidMDTStringException
     * @throws InvalidMDTDungeonException
     * @throws Throwable
     */
    public function createDraftFromMdtString(
        DungeonRoute $original,
        string       $mdtString,
        Collection   $warnings,
        ?int         $discardExistingDraftId = null,
    ): DungeonRoute;

    /**
     * Counts, per kind, the content $original holds that an MDT import draft replaces - only the kinds that
     * are present.
     *
     * @return array<string, int> Keyed by a DungeonRouteUpgradeDraftService::MDT_IMPORT_LOSS_* kind.
     */
    public function getMdtImportContentLoss(DungeonRoute $original): array;

    /**
     * Replaces the contents and settings of the draft's original with the draft's, preserving the
     * original's identity, and deletes the draft.
     *
     * @param  bool         $enforcePublishInvariant Whether a published original may only be replaced by a draft that
     *                                               killed all required enemies. The Auto Route Creator passes false:
     *                                               its routes are published by construction and an imperfect enemy
     *                                               match is a routine outcome of a combat log, so enforcing it would
     *                                               turn a normal miss into a failed regeneration (#4297).
     * @return DungeonRoute The original, refreshed.
     *
     * @throws UpgradeDraftGoneException When the draft was applied, discarded or taken over concurrently.
     * @throws UpgradeDraftException     When $draft is not a draft, or its original no longer exists.
     * @throws Throwable
     */
    public function apply(DungeonRoute $draft, bool $enforcePublishInvariant = true): DungeonRoute;

    /**
     * Deletes the draft, leaving its original untouched.
     *
     * @throws UpgradeDraftException When $draft is not a draft.
     * @throws Throwable
     */
    public function discard(DungeonRoute $draft): void;
}
