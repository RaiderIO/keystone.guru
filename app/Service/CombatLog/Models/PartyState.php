<?php

namespace App\Service\CombatLog\Models;

use App\Logic\CombatLog\BaseEvent;
use App\Logic\CombatLog\CombatEvents\AdvancedCombatLogEvent;
use App\Logic\CombatLog\CombatEvents\Suffixes\AuraApplied;
use App\Logic\CombatLog\CombatEvents\Suffixes\CastSuccess;
use App\Logic\CombatLog\CombatEvents\Suffixes\Heal;
use App\Logic\CombatLog\CombatEvents\Suffixes\Resurrect;
use App\Logic\CombatLog\Guid\Player;
use App\Logic\CombatLog\SpecialEvents\CombatantInfo;
use App\Logic\CombatLog\SpecialEvents\EncounterEnd;
use App\Logic\CombatLog\SpecialEvents\EncounterStart;
use App\Logic\CombatLog\SpecialEvents\SpellResurrect;
use App\Logic\CombatLog\SpecialEvents\UnitDied;
use Illuminate\Support\Collection;

class PartyState
{
    private const DEAD  = 0;
    private const ALIVE = 1;

    private Collection $alivePartyMembers;

    private Collection $deadPartyMembers;

    /** @var bool Keep track if we're currently in an encounter */
    private bool $inEncounter;

    public function __construct()
    {
        $this->alivePartyMembers = collect();
        $this->deadPartyMembers  = collect();
    }

    /**
     * @param BaseEvent $combatLogEvent
     * @return void
     */
    public function parse(BaseEvent $combatLogEvent): void
    {
        if ($combatLogEvent instanceof CombatantInfo) {
            // We assume people don't start encounters with people dead
            $this->playerResurrected($combatLogEvent->getPlayerGuid()->getGuid());
        } else if ($combatLogEvent instanceof UnitDied) {
            $deadGuid = $combatLogEvent->getGenericData()->getDestGuid()->getGuid();
            if ($this->alivePartyMembers->has($deadGuid)) {
                $this->playerDied($deadGuid);
            }
        } else if ($combatLogEvent instanceof EncounterStart) {
            $this->inEncounter = true;
        } else if ($combatLogEvent instanceof EncounterEnd) {
            $this->inEncounter = false;
            if ($this->alivePartyMembers->isEmpty()) {
                // We wiped and the encounter ended. Everyone releases
                $this->resurrectAllPlayers();
            }
        } else if ($combatLogEvent instanceof SpellResurrect) {
            $aliveGuid = $combatLogEvent->getGenericData()->getDestGuid()->getGuid();
            $this->playerResurrected($aliveGuid);
        } else if ($combatLogEvent instanceof AdvancedCombatLogEvent) {
            // Check if a dead player released and they've come back in the fray (we do not get events for this)
            $guid = $combatLogEvent->getGenericData()->getSourceGuid();
            if ($guid instanceof Player) {
                if ($this->deadPartyMembers->has($guid->getGuid())) {
                    // If they start casting, gain a buff or perform a heal we can say that they live again
                    // There's probably more but this is good enough for now
                    if ($combatLogEvent->getSuffix() instanceof CastSuccess ||
                        $combatLogEvent->getSuffix() instanceof AuraApplied ||
                        $combatLogEvent->getSuffix() instanceof Heal) {
                        // If the player did anything except periodic damage - they are alive again!
                        $this->playerResurrected($guid->getGuid());
                    }
                }
            }
        }
    }

    /**
     * @return bool
     */
    public function isPartyWiped(): bool
    {
        // If we found alive party members, we haven't wiped yet
        return $this->alivePartyMembers->isEmpty();
    }

    /**
     * @param string $guid
     * @return void
     */
    private function playerDied(string $guid): void
    {
        $this->alivePartyMembers->forget($guid);
        $this->deadPartyMembers->put($guid, self::DEAD);
    }

    /**
     * @param string $guid
     * @return void
     */
    private function playerResurrected(string $guid): void
    {
        $this->deadPartyMembers->forget($guid);
        $this->alivePartyMembers->put($guid, self::ALIVE);
    }

    /**
     * @return void
     */
    private function resurrectAllPlayers(): void
    {
        foreach ($this->deadPartyMembers as $guid => $state) {
            $this->alivePartyMembers->put($guid, self::ALIVE);
        }
    }

}
