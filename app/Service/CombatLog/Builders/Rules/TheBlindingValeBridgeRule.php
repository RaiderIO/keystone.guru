<?php

namespace App\Service\CombatLog\Builders\Rules;

use App\Models\Dungeon;
use App\Models\DungeonKey;
use App\Models\Enemy;
use App\Models\Npc\NpcId;
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
    /** @var array<int, int> The EnemyPack groups making up the packs on top of the bridge */
    private const array BRIDGE_ENEMY_PACK_GROUPS = [44, 45, 46];

    /** @var array<int, int> The EnemyPack groups underneath the bridge - they only spawn once Ruia is dead */
    private const array UNDER_BRIDGE_ENEMY_PACK_GROUPS = [47, 48, 49, 50, 54];

    /** @var array<int, string> Enemies underneath the bridge named individually because no pack reliably names them */
    private const array UNDER_BRIDGE_ENEMY_UNIQUE_KEYS = ['245484-5', '245484-6', '245484-7'];

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
        if ($this->lightwardenRuiaKilled || $npcId !== NpcId::LIGHTWARDEN_RUIA->value) {
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
     * The three Lightfeather Petalwings underneath the bridge are excluded by unique key rather than by pack, and
     * that check runs first: MDT groups them on some mapping versions and not on others, so neither their presence
     * in a pack nor any one group number describes them across the mapping versions a route can be built on.
     *
     * Note this blocks rather than prefers. A preference tier would outrank distance entirely, so it overrode correct
     * matches: a kill standing exactly on top of group 48's enemy resolved to a group 45 enemy 15 yards away instead.
     * Excluding the packs that do not exist yet gets the same effect without that failure mode, because it removes
     * candidates rather than reordering them.
     */
    public function isEnemyEligible(Enemy $enemy): bool
    {
        if (in_array($enemy->getUniqueKey(), self::UNDER_BRIDGE_ENEMY_UNIQUE_KEYS, true)) {
            return $this->lightwardenRuiaKilled;
        }

        if ($enemy->enemy_pack_id === null) {
            return true;
        }

        $group = $enemy->enemyPack->group;

        return $this->lightwardenRuiaKilled
            ? !in_array($group, self::BRIDGE_ENEMY_PACK_GROUPS, true)
            : !in_array($group, self::UNDER_BRIDGE_ENEMY_PACK_GROUPS, true);
    }
}
