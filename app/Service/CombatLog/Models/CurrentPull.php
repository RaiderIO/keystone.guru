<?php

namespace App\Service\CombatLog\Models;

use App\Logic\CombatLog\BaseEvent;
use App\Logic\CombatLog\CombatEvents\AdvancedCombatLogEvent;
use App\Logic\CombatLog\CombatEvents\GenericData;
use App\Logic\CombatLog\CombatEvents\Prefixes\SpellBuilding;
use App\Logic\CombatLog\CombatEvents\Prefixes\SpellPeriodic;
use App\Logic\CombatLog\CombatEvents\Suffixes\Damage;
use App\Logic\CombatLog\Guid\Creature;
use App\Logic\CombatLog\Guid\Evade;
use App\Logic\CombatLog\SpecialEvents\ChallengeModeEnd;
use App\Logic\CombatLog\SpecialEvents\ChallengeModeStart;
use App\Logic\CombatLog\SpecialEvents\UnitDied;
use App\Models\Spell;
use App\Service\CombatLog\Models\ResultEvents\BaseResultEvent;
use App\Service\CombatLog\Models\ResultEvents\EnemyEngaged;
use App\Service\CombatLog\Models\ResultEvents\EnemyKilled;
use Illuminate\Support\Collection;

class CurrentPull
{
    /** @var Collection|BaseResultEvent[] */
    private Collection $resultEvents;
    /** @var Collection|int[] */
    private Collection $validNpcIds;
    private Collection $currentPull;
    private Collection $killedEnemies;

    /** @var bool */
    private bool $challengeModeStarted = false;

    public function __construct(Collection $resultEvents, Collection $validNpcIds)
    {
        $this->resultEvents = $resultEvents;
        $this->validNpcIds   = $validNpcIds;
        $this->currentPull   = collect();
        $this->killedEnemies = collect();
    }

    /**
     * @param BaseEvent $combatLogEvent
     * @return bool
     */
    public function parse(BaseEvent $combatLogEvent): bool
    {
        // First, we wait for the challenge mode to start
        if ($combatLogEvent instanceof ChallengeModeStart) {
            $this->currentPull          = collect();
            $this->challengeModeStarted = true;
            return false;
        }

        // If it hasn't started yet, we don't process anything
        if (!$this->challengeModeStarted) {
            return false;
        }

        // If we ended it, stop all processing and drop combat of all enemies
        if ($combatLogEvent instanceof ChallengeModeEnd) {
            $this->currentPull          = collect();
            $this->challengeModeStarted = false;
            return false;
        }

        // If a unit has died..
        if ($combatLogEvent instanceof UnitDied) {
            $destGuid = $combatLogEvent->getGenericData()->getDestGuid();
            // And it's part of our current pull (it usually will be but doesn't have to be)
            if ($this->currentPull->has($destGuid->getGuid())) {
                // Then we're interested in the first time we saw this enemy
                $engagedEvent = $this->currentPull->get($destGuid->getGuid());
                // Push a new result event - we successfully killed this enemy, and it gave count!
                $this->resultEvents->push((new EnemyEngaged($engagedEvent, $destGuid)));
                // Kill this enemy as well. We push as 2 separate events, so we can keep track of combat state
                $this->resultEvents->push((new EnemyKilled($combatLogEvent)));
                // This enemy is no longer part of our current pull
                $this->currentPull->forget($destGuid->getGuid());

                // We have officially killed this enemy
                $this->killedEnemies->push($combatLogEvent->getGenericData()->getDestGuid());
                return true;
            }
        }

        // Ignore all irrelevant non-combat events going forward
        if ($this->isEnemyCombatLogEntry($combatLogEvent)) {
            /** @var AdvancedCombatLogEvent $combatLogEvent */
            // Evade means we are no longer in combat with this enemy, so we must drop aggro
            if ($combatLogEvent->getAdvancedData()->getInfoGuid() instanceof Evade) {
                $this->currentPull->forget($combatLogEvent->getGenericData()->getDestGuid()->getGuid());
                return false;
            }

            // Check if this combat event is relevant and if it has a new NPC that we're interested in
            $newEnemyGuid = $this->hasGenericDataNewEnemy($combatLogEvent->getGenericData());
            if ($newEnemyGuid !== null) {
                // If it does we want to keep this event
                $this->currentPull->put($newEnemyGuid, $combatLogEvent);
                return true;
            }
        }


        return false;
    }

    /**
     * Notify the current pull that the party has wiped.
     *
     * @return void
     */
    public function partyWiped(): void
    {
        // We no longer have any enemies in-combat with us
        $this->currentPull = collect();
    }

    /**
     * Combat log is empty - we no longer have any events left. Perform sanity checks.
     *
     * @return void
     */
    public function noMoreEvents(): void
    {
        if ($this->currentPull->isNotEmpty()) {
            dd(['We have unkilled enemies!', $this->currentPull->toArray()]);
        }
    }


    /**
     * @param BaseEvent $combatLogEvent
     * @return bool
     */
    private function isEnemyCombatLogEntry(BaseEvent $combatLogEvent): bool
    {
        // We skip all non-advanced combat log events, we need positional information of NPCs.
        if (!($combatLogEvent instanceof AdvancedCombatLogEvent)) {
            return false;
        }

        // Skip events that are not damage - they contain the location of the source (the player usually)
        if (!($combatLogEvent->getSuffix() instanceof Damage)) {
            return false;
        }
        // Spells return the location of the source, not the target.
        // So for non-creatures (such as players) we don't care about them since they can be 0..40 yards off the mark
        // But if the source is the creature itself we ARE interested in everything it can throw at us.
        $sourceGuid = $combatLogEvent->getGenericData()->getSourceGuid();
        if (!($sourceGuid instanceof Creature)) {
            if ($combatLogEvent->getPrefix() instanceof Spell) {
                return false;
            }
            if ($combatLogEvent->getPrefix() instanceof SpellBuilding) {
                return false;
            }
            if ($combatLogEvent->getPrefix() instanceof SpellPeriodic) {
                return false;
            }
        }


        return true;
    }

    /**
     * @param GenericData $genericData
     * @return string|null
     */
    private function hasGenericDataNewEnemy(GenericData $genericData): ?string
    {
        $guids = [
            $genericData->getSourceGuid(),
            $genericData->getDestGuid(),
        ];

        $result = null;
        foreach ($guids as $guid) {
            // We're not interested in events if they don't contain a creature
            if (!$guid instanceof Creature) {
                continue;
            }

            // Invalid NPC ID, ignore it since it can never be part of the route anyways
            if ($this->validNpcIds->search($guid->getId()) === false) {
                continue;
            }

            // We already killed this enemy - don't aggro it again (we may have dots from this enemy on our players)
            if ($this->killedEnemies->search($guid->getGuid()) !== false) {
                continue;
            }

            if ($this->currentPull->has($guid->getGuid()) === false) {
                // We MAY find 2 new enemies if there's perhaps a combat log event between two enemies and we may miss this
                // But this will happen so little that it's not worth the complexity plus the 2nd enemy will get picked up
                // when it's hit by a player anyways
                $result = $guid->getGuid();
                break;
            }
        }

        return $result;
    }
}