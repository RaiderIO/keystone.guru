<?php

namespace App\Service\CombatLog\Builders\Rules;

use App\Models\Dungeon;
use App\Models\DungeonKey;
use App\Models\Enemy;
use App\Models\Npc\NpcId;
use App\Service\CombatLog\Builders\Logging\DungeonRouteBuilderLoggingInterface;

/**
 * Awards the kills for the Temple of Sethraliss enemies that despawn instead of dying.
 *
 * The Static Anomalies are not killed: they despawn and convert into a ball, which is a separate effect with its own
 * npc, and Galvazzt only spawns once all of them have been dealt with. No death for an anomaly ever reaches us - not
 * in the combat log, and not in what Raider.IO sends the Auto Route Creator - so without compensation the route is
 * short every anomaly and the enemy forces they are worth.
 *
 * Galvazzt's own death is the signal that they are all gone, since the party cannot have reached him otherwise. He
 * carries two npc_ids across the mapping versions we still build routes for, and either one means the same thing.
 *
 * The Avatar of Sethraliss is not killed either, but for a different reason: it starts at a fraction of its health and
 * the encounter is won by healing it back to full. It never takes a lethal hit, so no death for it exists anywhere -
 * and being the final boss, there is no later death to award it off either. Completing the dungeon is the only thing
 * that implies it, so it is awarded when the run finished successfully.
 *
 * Awarding is idempotent per npc: an enemy that did reach us normally, or that an earlier award already covered, is
 * never awarded twice.
 */
class TempleOfSethralissDespawningEnemiesRule extends AbstractDungeonRouteBuilderRule
{
    /** @var array<int, int> Galvazzt as mapped in the dungeon's original run and in its return - both are live */
    private const array NPC_IDS_GALVAZZT = [
        NpcId::GALVAZZT->value,
        NpcId::GALVAZZT_RESTORED->value,
    ];

    public function __construct(private readonly DungeonRouteBuilderLoggingInterface $log)
    {
    }

    public function appliesToDungeon(Dungeon $dungeon): bool
    {
        return $dungeon->key === DungeonKey::TEMPLE_OF_SETHRALISS->value;
    }

    public function onEnemyDied(int $npcId, ?Enemy $resolvedEnemy): array
    {
        parent::onEnemyDied($npcId, $resolvedEnemy);

        if (!in_array($npcId, self::NPC_IDS_GALVAZZT, true)) {
            return [];
        }

        $awardedNpcIds = $this->awardUnaccountedNpcIds([NpcId::STATIC_ANOMALY->value]);

        if ($awardedNpcIds !== []) {
            $this->log->templeOfSethralissDespawningEnemiesRuleEnemyKillsAwarded($npcId, $awardedNpcIds);
        }

        return $awardedNpcIds;
    }

    public function onRunFinished(?bool $success): array
    {
        // A run that did not complete never reached the Avatar's full health, and one that reported no outcome at all
        // is not evidence that it did
        if ($success !== true) {
            return [];
        }

        $awardedNpcIds = $this->awardUnaccountedNpcIds([NpcId::AVATAR_OF_SETHRALISS->value]);

        if ($awardedNpcIds !== []) {
            $this->log->templeOfSethralissDespawningEnemiesRuleRunFinishedEnemyKillsAwarded($awardedNpcIds);
        }

        return $awardedNpcIds;
    }
}
