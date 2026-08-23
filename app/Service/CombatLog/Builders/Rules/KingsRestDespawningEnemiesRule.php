<?php

namespace App\Service\CombatLog\Builders\Rules;

use App\Models\Dungeon;
use App\Models\DungeonKey;
use App\Models\Enemy;
use App\Service\CombatLog\Builders\Logging\DungeonRouteBuilderLoggingInterface;

/**
 * Awards the kills for the King's Rest enemies that despawn instead of dying.
 *
 * The Council of Tribes, the Shadow of Zul and King Dazar's encounter are all fought against ghosts: when they are
 * beaten they simply vanish, and no death for them ever reaches us - not in the combat log, and not in what Raider.IO
 * sends the Auto Route Creator. Without compensation the route silently misses the third boss, a required trash pull
 * and the final boss.
 *
 * Every one of them does have a neighbour whose death we do get, so each is awarded off that neighbour instead:
 *
 * - The Council of Tribes, off Zanazal the Wise's totems. Only one award fires no matter how many totems die.
 * - The Shadow of Zul, off a Minion of Zul from its own pack - but only once the Council is down, so an early
 *   trash kill cannot award it.
 * - King Dazar and T'zala, off Reban, the last add before the encounter starts.
 *
 * The Shadow of Zul is a required kill that cannot be skipped: the party has to be past it to reach King Dazar at all.
 * So if Reban dies and it has still not been accounted for, it is awarded there too - in King Dazar's own pull, which
 * Wotuu explicitly preferred over synthesising an extra pull in front of the boss.
 *
 * Awarding is idempotent per npc: an enemy that did reach us normally, or that an earlier award already covered, is
 * never awarded twice.
 */
class KingsRestDespawningEnemiesRule extends AbstractDungeonRouteBuilderRule
{
    /** @var array<int, int> Zanazal the Wise's totems - the only Council of Tribes npcs whose deaths do reach us */
    private const array NPC_IDS_COUNCIL_OF_TRIBES_TOTEMS = [
        135761, // Thundering Totem
        135764, // Explosive Totem
        135765, // Torrent Totem
    ];

    /**
     * @var array<int, int> The three Council of Tribes ghosts, as they are mapped currently. The 135470/135472/135475
     *                      set carrying the same three names is not part of the current mapping.
     */
    private const array NPC_IDS_COUNCIL_OF_TRIBES = [
        269808, // Aka'ali the Conqueror
        269810, // Zanazal the Wise
        269811, // Kula the Butcher
    ];

    /**
     * @var int Minion of Zul. Deliberately not 133943, which carries the same name but is mapped in the early dungeon
     *          packs rather than in the Shadow of Zul's own pack.
     */
    private const int NPC_ID_MINION_OF_ZUL = 138493;

    private const int NPC_ID_SHADOW_OF_ZUL = 138489;

    /** @var int Reban, the last add before King Dazar's encounter, and the only one of the three whose death reaches us */
    private const int NPC_ID_REBAN = 136984;

    private const int NPC_ID_TZALA = 136976;

    private const int NPC_ID_KING_DAZAR = 136160;

    /** @var array<int, bool> npc_id => true for every enemy that has died, or that an award has accounted for */
    private array $defeatedNpcIds = [];

    public function __construct(private readonly DungeonRouteBuilderLoggingInterface $log)
    {
    }

    public function appliesToDungeon(Dungeon $dungeon): bool
    {
        return $dungeon->key === DungeonKey::KINGS_REST->value;
    }

    public function onEnemyDied(int $npcId, ?Enemy $resolvedEnemy): array
    {
        $this->defeatedNpcIds[$npcId] = true;

        if (in_array($npcId, self::NPC_IDS_COUNCIL_OF_TRIBES_TOTEMS, true)) {
            return $this->awardEnemyKills($npcId, self::NPC_IDS_COUNCIL_OF_TRIBES);
        }

        // Only once the Council is down - the party is nowhere near the Shadow of Zul before that
        if ($npcId === self::NPC_ID_MINION_OF_ZUL && $this->isCouncilOfTribesDefeated()) {
            return $this->awardEnemyKills($npcId, [self::NPC_ID_SHADOW_OF_ZUL]);
        }

        if ($npcId === self::NPC_ID_REBAN) {
            // The Shadow of Zul is listed first only for readability - awardEnemyKills() skips it when it is already
            // accounted for, which is the normal case when the party killed a Minion of Zul on the way here.
            return $this->awardEnemyKills($npcId, [
                self::NPC_ID_SHADOW_OF_ZUL,
                self::NPC_ID_TZALA,
                self::NPC_ID_KING_DAZAR,
            ]);
        }

        return [];
    }

    /**
     * @param array<int, int> $npcIds
     *
     * @return array<int, int>
     */
    private function awardEnemyKills(int $triggerNpcId, array $npcIds): array
    {
        $awardedNpcIds = [];

        foreach ($npcIds as $npcId) {
            if (isset($this->defeatedNpcIds[$npcId])) {
                continue;
            }

            $this->defeatedNpcIds[$npcId] = true;
            $awardedNpcIds[]              = $npcId;
        }

        if ($awardedNpcIds !== []) {
            $this->log->kingsRestDespawningEnemiesRuleEnemyKillsAwarded($triggerNpcId, $awardedNpcIds);
        }

        return $awardedNpcIds;
    }

    private function isCouncilOfTribesDefeated(): bool
    {
        foreach (self::NPC_IDS_COUNCIL_OF_TRIBES as $npcId) {
            if (!isset($this->defeatedNpcIds[$npcId])) {
                return false;
            }
        }

        return true;
    }
}
