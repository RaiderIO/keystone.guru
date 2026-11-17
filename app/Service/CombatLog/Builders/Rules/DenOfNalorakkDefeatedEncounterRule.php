<?php

namespace App\Service\CombatLog\Builders\Rules;

use App\Models\Dungeon;
use App\Models\DungeonKey;
use App\Models\Enemy;
use App\Models\Npc\NpcId;
use App\Service\CombatLog\Builders\Logging\DungeonRouteBuilderLoggingInterface;

/**
 * Awards the kills for the enemies that share Nalorakk's pack, once Nalorakk himself is defeated.
 *
 * Nalorakk turns friendly at 1hp instead of dying, which the combat filter turns into a kill. Zul'jarra and the Echo of
 * Nalorakk fight alongside him but never take enough damage to be considered defeated on their own; the encounter ends
 * with them still standing, so no death for either ever reaches us.
 *
 * Awarding is idempotent per npc: an enemy that did reach us normally, or that an earlier award already covered, is
 * never awarded twice.
 */
class DenOfNalorakkDefeatedEncounterRule extends AbstractDungeonRouteBuilderRule
{
    public function __construct(private readonly DungeonRouteBuilderLoggingInterface $log)
    {
    }

    public function appliesToDungeon(Dungeon $dungeon): bool
    {
        return $dungeon->key === DungeonKey::DEN_OF_NALORAKK->value;
    }

    public function onEnemyDied(int $npcId, ?Enemy $resolvedEnemy): array
    {
        parent::onEnemyDied($npcId, $resolvedEnemy);

        if ($npcId !== NpcId::NALORAKK->value) {
            return [];
        }

        $awardedNpcIds = $this->awardUnaccountedNpcIds([
            NpcId::ZULJARRA->value,
            NpcId::ECHO_OF_NALORAKK->value,
        ]);

        if ($awardedNpcIds !== []) {
            $this->log->denOfNalorakkDefeatedEncounterRuleEnemyKillsAwarded($npcId, $awardedNpcIds);
        }

        return $awardedNpcIds;
    }
}
