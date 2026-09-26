<?php

namespace App\Service\DungeonRoute;

use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Mapping\MappingVersion;
use App\Service\DungeonRoute\Dtos\MappingVersionUpgradeDiff;

/**
 * Explains what a mapping version upgrade did to a dungeon route.
 *
 * The upgrade matches a route's pull enemies on identity (the NPC id MDT knows, plus MDT's
 * clone index) and drops whatever the new mapping version has no enemy for. That is invisible in the editor -
 * a pull simply holds fewer enemies than the author left in it - so this puts it in front of them instead.
 */
interface MappingVersionUpgradeDiffServiceInterface
{
    /**
     * The diff of the upgrade that produced $draft, or null when the route it upgrades is gone.
     *
     * Deliberately describes what the upgrade changed - the original against the draft's mapping version -
     * rather than what the author has left to repair. The one exception is the required enemies section,
     * which reads the draft's current pulls so that it empties out as the author fixes it.
     */
    public function diffForUpgradeDraft(DungeonRoute $draft): ?MappingVersionUpgradeDiff;

    /**
     * The diff of upgrading $original onto $newMappingVersion.
     *
     * @param DungeonRoute|null $upgradedDungeonRoute The route the upgrade actually produced, when it has already
     *                                                run. It is the only source of the post-upgrade enemy forces
     *                                                total and of which required enemies are still unkilled; without
     *                                                it both are derived from the enemies the upgrade would keep.
     */
    public function diff(
        DungeonRoute   $original,
        MappingVersion $newMappingVersion,
        ?DungeonRoute  $upgradedDungeonRoute = null,
    ): MappingVersionUpgradeDiff;
}
