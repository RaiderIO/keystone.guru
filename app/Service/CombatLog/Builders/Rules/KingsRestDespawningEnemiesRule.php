<?php

namespace App\Service\CombatLog\Builders\Rules;

use App\Models\Dungeon;
use App\Models\DungeonKey;
use App\Models\Enemy;
use App\Models\Npc\NpcId;
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
 * - The Council of Tribes, off Zanazal the Wise's totems. The first totem to die also awards the other two totems
 *   alongside the three bosses, so the whole encounter lands in one pull instead of the totems' real (later) deaths
 *   spawning a second one.
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
        NpcId::THUNDERING_TOTEM->value,
        NpcId::EXPLOSIVE_TOTEM->value,
        NpcId::TORRENT_TOTEM->value,
    ];

    /**
     * @var array<int, int> The three Council of Tribes ghosts, as they are mapped currently. The 135470/135472/135475
     *                      set carrying the same three names is not part of the current mapping.
     */
    private const array NPC_IDS_COUNCIL_OF_TRIBES = [
        NpcId::AKAALI_THE_CONQUEROR->value,
        NpcId::ZANAZAL_THE_WISE->value,
        NpcId::KULA_THE_BUTCHER->value,
    ];

    public function __construct(private readonly DungeonRouteBuilderLoggingInterface $log)
    {
    }

    public function appliesToDungeon(Dungeon $dungeon): bool
    {
        return $dungeon->key === DungeonKey::KINGS_REST->value;
    }

    public function onEnemyDied(int $npcId, ?Enemy $resolvedEnemy): array
    {
        $this->markDefeated($npcId);

        if (in_array($npcId, self::NPC_IDS_COUNCIL_OF_TRIBES_TOTEMS, true)) {
            return $this->award($npcId, [
                ...self::NPC_IDS_COUNCIL_OF_TRIBES_TOTEMS,
                ...self::NPC_IDS_COUNCIL_OF_TRIBES,
            ]);
        }

        // Only once the Council is down - the party is nowhere near the Shadow of Zul before that
        if ($npcId === NpcId::MINION_OF_ZUL->value && $this->isCouncilOfTribesDefeated()) {
            return $this->award($npcId, [NpcId::SHADOW_OF_ZUL->value]);
        }

        if ($npcId === NpcId::REBAN->value) {
            // The Shadow of Zul is listed first only for readability - award() skips it when it is already
            // accounted for, which is the normal case when the party killed a Minion of Zul on the way here.
            return $this->award($npcId, [
                NpcId::SHADOW_OF_ZUL->value,
                NpcId::TZALA->value,
                NpcId::KING_DAZAR->value,
            ]);
        }

        return [];
    }

    /**
     * @param array<int, int> $npcIds
     *
     * @return array<int, int>
     */
    private function award(int $triggerNpcId, array $npcIds): array
    {
        $awardedNpcIds = $this->awardUnaccountedNpcIds($npcIds);

        if ($awardedNpcIds !== []) {
            $this->log->kingsRestDespawningEnemiesRuleEnemyKillsAwarded($triggerNpcId, $awardedNpcIds);
        }

        return $awardedNpcIds;
    }

    private function isCouncilOfTribesDefeated(): bool
    {
        foreach (self::NPC_IDS_COUNCIL_OF_TRIBES as $npcId) {
            if (!$this->isDefeated($npcId)) {
                return false;
            }
        }

        return true;
    }
}
