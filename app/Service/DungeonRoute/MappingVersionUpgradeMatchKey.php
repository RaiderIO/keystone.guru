<?php

namespace App\Service\DungeonRoute;

use App\Models\Enemy;

/**
 * The identity a mapping version upgrade re-resolves a route's pull enemies and raid markers on.
 *
 * Both sides of the upgrade join live here so that the diff shown to the author and the upgrade that
 * produced it can never disagree about what matched.
 *
 * @see \App\Repositories\Database\KillZone\KillZoneEnemyRepository::updateEnemyIdsByMappingVersion()
 * @see \App\Repositories\Database\DungeonRoute\DungeonRouteEnemyRaidMarkerRepository::updateEnemyIdsByMappingVersion()
 */
final class MappingVersionUpgradeMatchKey
{
    /**
     * The key of a kill zone enemy, or null when it can never match.
     *
     * The kill zone join compares both columns with a plain `=`, so a null on either side matches nothing
     * at all - such a pull enemy is dropped by the upgrade regardless of what the new mapping version holds.
     */
    public static function forPullEnemy(?int $npcId, ?int $mdtId): ?string
    {
        if ($npcId === null || $mdtId === null) {
            return null;
        }

        return sprintf('%d-%d', $npcId, $mdtId);
    }

    /**
     * The key of an enemy as a kill zone enemy matches it, or null when nothing can match it.
     */
    public static function forEnemyAsPullEnemy(Enemy $enemy): ?string
    {
        return self::forPullEnemy($enemy->mdt_npc_id ?? $enemy->npc_id, $enemy->mdt_id);
    }

    /**
     * The key of a raid marker, or null when it can never match. Doubles as the identity an enemy is recognised
     * by across mapping versions wherever the question is "is this the same enemy" rather than "does the upgrade
     * match it", since it is the more forgiving of the two.
     *
     * Unlike the kill zone join this one compares mdt_id null-safely (`<=>`), so enemies placed outside of
     * an MDT import - which legitimately carry no mdt_id - keep their markers across an upgrade.
     */
    public static function forRaidMarker(?int $npcId, ?int $mdtId): ?string
    {
        if ($npcId === null) {
            return null;
        }

        return sprintf('%d-%s', $npcId, $mdtId ?? 'null');
    }

    /**
     * The key of an enemy as a raid marker matches it, or null when nothing can match it.
     */
    public static function forEnemyAsRaidMarker(Enemy $enemy): ?string
    {
        return self::forRaidMarker($enemy->mdt_npc_id ?? $enemy->npc_id, $enemy->mdt_id);
    }
}
