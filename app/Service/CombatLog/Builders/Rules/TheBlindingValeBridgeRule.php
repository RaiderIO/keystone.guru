<?php

namespace App\Service\CombatLog\Builders\Rules;

use App\Models\Dungeon;
use App\Models\DungeonKey;
use App\Models\Enemy;
use App\Service\CombatLog\Builders\Logging\DungeonRouteBuilderLoggingInterface;

/**
 * Stops the trash underneath The Blinding Vale's bridge from pulling the enemy packs on top of it.
 *
 * Both sit on floor 408 at near identical ingame X/Y - the bridge is above the path west, and the builder has no Z
 * axis - and they share npc_ids, so nothing in a purely spatial match distinguishes them. The party crosses the
 * bridge through groups 44, 45 and 46 on the way to Ikuzz, then walks underneath it toward Ziekket once Lightwarden
 * Ruia is dead.
 *
 * Ruia's death is the only thing that separates the two traversals: which of the first two bosses died first does not
 * matter, because the bridge is crossed either way.
 */
class TheBlindingValeBridgeRule extends AbstractDungeonRouteBuilderRule
{
    /** @var int Lightwarden Ruia, the third boss - the party is past the bridge for good once she dies */
    private const int NPC_ID_LIGHTWARDEN_RUIA = 245912;

    /** @var array<int, int> The EnemyPack groups making up the packs on top of the bridge */
    private const array BRIDGE_ENEMY_PACK_GROUPS = [44, 45, 46];

    private bool $lightwardenRuiaKilled = false;

    public function __construct(private readonly DungeonRouteBuilderLoggingInterface $log)
    {
    }

    public function appliesToDungeon(Dungeon $dungeon): bool
    {
        return $dungeon->key === DungeonKey::THE_BLINDING_VALE->value;
    }

    public function onEnemyDied(int $npcId, ?Enemy $resolvedEnemy): void
    {
        if ($this->lightwardenRuiaKilled || $npcId !== self::NPC_ID_LIGHTWARDEN_RUIA) {
            return;
        }

        $this->lightwardenRuiaKilled = true;

        $this->log->theBlindingValeBridgeRuleBridgeEnemyPackGroupsBlocked(
            $npcId,
            self::BRIDGE_ENEMY_PACK_GROUPS,
        );
    }

    /**
     * A hard exclusion by design: walking underneath the bridge must not be able to pull the packs on top of it, even
     * when no other enemy matches at all. An unmatched kill is recorded as an enemy failure, which is a better
     * outcome than a pull that cannot be walked.
     *
     * Note this deliberately only blocks - it does not also prefer these groups while Ruia is alive. A preference
     * would outrank distance entirely, so it overrode correct matches: a kill standing exactly on top of group 48's
     * enemy resolved to a group 45 enemy 15 yards away instead. The v8 mapping already puts the right pack 4-11x
     * closer than the wrong one on both traversals, so there was nothing for a preference to fix.
     */
    public function isEnemyEligible(Enemy $enemy): bool
    {
        return !$this->lightwardenRuiaKilled || !$this->isBridgeEnemy($enemy);
    }

    private function isBridgeEnemy(Enemy $enemy): bool
    {
        return $enemy->enemy_pack_id !== null &&
            in_array($enemy->enemyPack->group, self::BRIDGE_ENEMY_PACK_GROUPS, true);
    }
}
