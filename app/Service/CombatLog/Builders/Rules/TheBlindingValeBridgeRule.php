<?php

namespace App\Service\CombatLog\Builders\Rules;

use App\Models\Dungeon;
use App\Models\DungeonKey;
use App\Models\Enemy;
use App\Service\CombatLog\Builders\Logging\DungeonRouteBuilderLoggingInterface;

/**
 * Keeps The Blinding Vale's bridge and the path underneath it from stealing each other's kills.
 *
 * Both sit on floor 408 at near identical ingame X/Y - the bridge is above the path west, and the builder has no Z
 * axis - and they share npc_ids, so nothing in a purely spatial match distinguishes them. The party crosses the
 * bridge through groups 44, 45 and 46 on the way to Ikuzz, then walks underneath it toward Ziekket once Lightwarden
 * Ruia is dead.
 *
 * Ruia's death is the only thing that separates the two traversals: which of the first two bosses died first does not
 * matter, because the bridge is crossed either way. So it cuts the run in two, and each half excludes the packs that
 * do not exist in it - the packs underneath while the party is still on the bridge, and the bridge packs afterwards.
 */
class TheBlindingValeBridgeRule extends AbstractDungeonRouteBuilderRule
{
    /** @var int Lightwarden Ruia, the third boss - the party is past the bridge for good once she dies */
    private const int NPC_ID_LIGHTWARDEN_RUIA = 245912;

    /** @var array<int, int> The EnemyPack groups making up the packs on top of the bridge */
    private const array BRIDGE_ENEMY_PACK_GROUPS = [44, 45, 46];

    /** @var array<int, int> The EnemyPack groups underneath the bridge - they only spawn once Ruia is dead */
    private const array UNDER_BRIDGE_ENEMY_PACK_GROUPS = [47, 48, 49, 50, 54, 57];

    private bool $lightwardenRuiaKilled = false;

    public function __construct(private readonly DungeonRouteBuilderLoggingInterface $log)
    {
    }

    public function appliesToDungeon(Dungeon $dungeon): bool
    {
        return $dungeon->key === DungeonKey::THE_BLINDING_VALE->value;
    }

    public function onEnemyDied(int $npcId, ?Enemy $resolvedEnemy): array
    {
        if ($this->lightwardenRuiaKilled || $npcId !== self::NPC_ID_LIGHTWARDEN_RUIA) {
            return [];
        }

        $this->lightwardenRuiaKilled = true;

        $this->log->theBlindingValeBridgeRuleBridgeEnemyPackGroupsBlocked(
            $npcId,
            self::BRIDGE_ENEMY_PACK_GROUPS,
        );

        return [];
    }

    /**
     * Hard exclusions by design, in both directions: neither traversal must be able to pull the other's packs, even
     * when no other enemy matches at all. An unmatched kill is recorded as an enemy failure, which is a better
     * outcome than a pull that cannot be walked.
     *
     * Note this blocks rather than prefers. A preference tier would outrank distance entirely, so it overrode correct
     * matches: a kill standing exactly on top of group 48's enemy resolved to a group 45 enemy 15 yards away instead.
     * Excluding the packs that do not exist yet gets the same effect without that failure mode, because it removes
     * candidates rather than reordering them.
     */
    public function isEnemyEligible(Enemy $enemy): bool
    {
        if ($enemy->enemy_pack_id === null) {
            return true;
        }

        $group = $enemy->enemyPack->group;

        return $this->lightwardenRuiaKilled
            ? !in_array($group, self::BRIDGE_ENEMY_PACK_GROUPS, true)
            : !in_array($group, self::UNDER_BRIDGE_ENEMY_PACK_GROUPS, true);
    }
}
