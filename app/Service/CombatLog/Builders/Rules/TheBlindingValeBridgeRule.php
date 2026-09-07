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
 * bridge on the way to Ikuzz, then walks underneath it toward Ziekket once Lightwarden Ruia is dead.
 *
 * Ruia's death is the only thing that separates the two traversals: which of the first two bosses died first does not
 * matter, because the bridge is crossed either way. So it cuts the run in two, and each half excludes the enemies
 * that do not exist in it - the ones underneath while the party is still on the bridge, and the ones on the bridge
 * afterwards.
 *
 * Enemies are named by unique key (mdt_npc_id-mdt_id) rather than by EnemyPack group: the key is what the MDT import
 * matches enemies on and it survives a re-import, while group numbers are per mapping version and MDT reassigns them
 * freely - 6.2.13 deleted four of the groups this rule used to name.
 */
class TheBlindingValeBridgeRule extends AbstractDungeonRouteBuilderRule
{
    /** @var array<int, string> The unique keys of the enemies on top of the bridge (MDT groups 44, 45 and 46) */
    private const array BRIDGE_ENEMY_UNIQUE_KEYS = [
        '245339-11', '245339-12', '245345-25', '254850-10',
        '245410-90', '245410-91', '245410-92', '245410-93', '245410-94',
        '245346-5', '245473-9', '245484-16',
    ];

    /** @var array<int, string> The unique keys of the enemies underneath the bridge - they only spawn once Ruia is dead */
    private const array UNDER_BRIDGE_ENEMY_UNIQUE_KEYS = [
        // MDT groups 47 through 50 and 54
        '245410-107', '245410-108', '245410-109', '245410-110', '245410-111',
        '245345-28', '245410-112', '245410-113',
        '245346-6',
        '245336-1', '245339-1', '245345-26', '245345-27', '245410-104', '245410-105', '245410-106',
        '245345-10', '245410-15', '245410-16', '245410-17', '245410-18', '245410-19',

        // The Lightfeather Petalwings, which MDT groups on some mapping versions and not on others
        '245484-5', '245484-6', '245484-7',
    ];

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

        $this->log->theBlindingValeBridgeRuleBridgeEnemiesBlocked(
            $npcId,
            self::BRIDGE_ENEMY_UNIQUE_KEYS,
        );

        return [];
    }

    /**
     * Hard exclusions by design, in both directions: neither traversal must be able to pull the other's enemies, even
     * when no other enemy matches at all. An unmatched kill is recorded as an enemy failure, which is a better
     * outcome than a pull that cannot be walked.
     *
     * Note this blocks rather than prefers. A preference tier would outrank distance entirely, so it overrode correct
     * matches: a kill standing exactly on top of an under-bridge enemy resolved to a bridge enemy 15 yards away
     * instead. Excluding the enemies that do not exist yet gets the same effect without that failure mode, because it
     * removes candidates rather than reordering them.
     */
    public function isEnemyEligible(Enemy $enemy): bool
    {
        $uniqueKey = $enemy->getUniqueKey();

        return $this->lightwardenRuiaKilled
            ? !in_array($uniqueKey, self::BRIDGE_ENEMY_UNIQUE_KEYS, true)
            : !in_array($uniqueKey, self::UNDER_BRIDGE_ENEMY_UNIQUE_KEYS, true);
    }
}
