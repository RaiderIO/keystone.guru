<?php

namespace App\Service\CombatLog\Models;

use App\Logic\CombatLog\BaseEvent;
use App\Logic\CombatLog\CombatEvents\AdvancedCombatLogEvent;
use App\Logic\CombatLog\CombatEvents\CombatLogEvent;
use App\Logic\CombatLog\CombatEvents\GenericData;
use App\Logic\CombatLog\CombatEvents\Prefixes\Spell;
use App\Logic\CombatLog\CombatEvents\Prefixes\SpellBuilding;
use App\Logic\CombatLog\CombatEvents\Prefixes\SpellPeriodic;
use App\Logic\CombatLog\CombatEvents\Suffixes\Damage;
use App\Logic\CombatLog\CombatEvents\Suffixes\Summon;
use App\Logic\CombatLog\Guid\Creature;
use App\Logic\CombatLog\Guid\Evade;
use App\Logic\CombatLog\SpecialEvents\ChallengeModeEnd;
use App\Logic\CombatLog\SpecialEvents\ChallengeModeStart;
use App\Logic\CombatLog\SpecialEvents\UnitDied;
use App\Logic\Utils\MathUtils;
use App\Service\CombatLog\Logging\CurrentPullLoggingInterface;
use App\Service\CombatLog\Models\ResultEvents\BaseResultEvent;
use App\Service\CombatLog\Models\ResultEvents\EnemyEngaged;
use App\Service\CombatLog\Models\ResultEvents\EnemyKilled;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;

class CurrentPull
{
    /** @var float[] The percentage (between 0 and 1) when certain enemies are considered defeated */
    private const DEFEATED_PERCENTAGE = [
        // Uldaman: Lost Dwarves, they kinda stop fighting when they reach below 10%
        184580 => 0.1,
        184581 => 0.1,
        184582 => 0.1,

        // Uldaman: Chrono-Lord Deios goes "ENOUGH" at 1hp, makes himself immune and teleports away
        184125 => 0.01,
        
        // Brackenhide Hollow: Decatriarch Wratheye is defeated at 5%
        186121 => 0.05
    ];

    /**
     * @var int The maximum distance since our absolute first sighting before we disregard any other events and return to the original distance.
     * If we aggroed an enemy using some spell (max range 40) and we proceed to drag the enemy 100 yards away before beating it to death, we're better
     * off taking the original position of the caster even if it's max 40 yards off. Better 40 yards than 100 yards.
     */
    private const MAX_LEASH_DISTANCE = 40;

    /** @var Collection|BaseResultEvent[] */
    private Collection $resultEvents;

    /** @var Collection|int[] */
    private Collection $validNpcIds;

    /** @var Collection|CombatLogEvent[] List of GUID => CombatLogEvent for the absolute first sighting of an enemy. Used as a backup */
    private Collection $firstEnemySightings;

    /** @var Collection|CombatLogEvent[] List of GUID => CombatLogEvent for all enemies that we are currently in combat with. */
    private Collection $currentPull;

    /** @var Collection|string[] List of GUIDs for all enemies that have been summoned. Summoned enemies are ignored by default. */
    private Collection $summonedEnemies;

    /** @var Collection|string[] List of GUIDs for all enemies that we have killed since the start. */
    private Collection $killedEnemies;

    /** @var bool */
    private bool $challengeModeStarted = false;

    /** @var CurrentPullLoggingInterface */
    protected $log;

    public function __construct(Collection $resultEvents, Collection $validNpcIds)
    {
        $this->resultEvents        = $resultEvents;
        $this->validNpcIds         = $validNpcIds;
        $this->firstEnemySightings = collect();
        $this->currentPull         = collect();
        $this->summonedEnemies     = collect();
        $this->killedEnemies       = collect();

        /** @var CurrentPullLoggingInterface $log */
        $log       = App::make(CurrentPullLoggingInterface::class);
        $this->log = $log;
    }

    /**
     * @param BaseEvent $combatLogEvent
     * @param int       $lineNr
     *
     * @return bool
     */
    public function parse(BaseEvent $combatLogEvent, int $lineNr): bool
    {
        // First, we wait for the challenge mode to start
        if ($combatLogEvent instanceof ChallengeModeStart) {
            $this->log->parseChallengeModeStarted($lineNr);
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
            $this->log->parseChallengeModeEnded($lineNr);
            $this->currentPull          = collect();
            $this->challengeModeStarted = false;

            return false;
        }

        // If a unit has died/is defeated
        if ($combatLogEvent instanceof UnitDied || $this->isEnemyDefeated($combatLogEvent) || $this->hasDeathAuraApplied($combatLogEvent)) {
            $destGuid = $combatLogEvent->getGenericData()->getDestGuid();
            $this->log->parseUnitDied($lineNr, $destGuid->getGuid());
            // And it's part of our current pull (it usually will be but doesn't have to be), and it also should not be killed already, AND also not summoned
            if ($this->currentPull->has($destGuid->getGuid()) &&
                $this->killedEnemies->search($destGuid->getGuid()) === false &&
                $this->summonedEnemies->search($destGuid->getGuid()) === false) {
                // Then we're interested in the first time we saw this enemy
                // Push a new result event - we successfully killed this enemy, and it gave count!
                $this->resultEvents->push((new EnemyEngaged($this->getEnemyEngagedEvent($destGuid), $destGuid)));
                // Kill this enemy as well. We push as 2 separate events, so we can keep track of combat state
                $this->resultEvents->push((new EnemyKilled($combatLogEvent)));
                // This enemy is no longer part of our current pull
                $this->currentPull->forget($destGuid->getGuid());

                // We have officially killed this enemy
                $this->killedEnemies->push($destGuid);

                $this->log->parseUnitInCurrentPullKilled($lineNr, $destGuid->getGuid());

                return true;
            }
        }

        $newEnemyGuid = null;
        if ($combatLogEvent instanceof CombatLogEvent) {
            if ($combatLogEvent instanceof AdvancedCombatLogEvent) {
                $newEnemyGuid = $this->hasGenericDataNewEnemy($combatLogEvent->getGenericData());
                if ($newEnemyGuid !== null && !$this->firstEnemySightings->has($newEnemyGuid)) {
                    $this->firstEnemySightings->put($newEnemyGuid, $combatLogEvent);
                    $this->log->parseUnitFirstSighted($lineNr, $newEnemyGuid);
                }
            }

            if ($combatLogEvent->getSuffix() instanceof Summon) {
                // Specially handle summoned enemies
                $this->summonedEnemies->push($combatLogEvent->getGenericData()->getDestGuid()->getGuid());
                $this->log->parseUnitSummoned($lineNr, $combatLogEvent->getGenericData()->getDestGuid()->getGuid());

                return false;
            }
        }

        // Ignore all irrelevant non-combat events going forward
        if ($this->isEnemyCombatLogEntry($combatLogEvent)) {
            /** @var AdvancedCombatLogEvent $combatLogEvent */
            // Dest GUID may be empty in case of SPELL_CAST_SUCCESS with cone/AoE attacks that don't have a target but hit everyone
            $destGuid = optional($combatLogEvent->getGenericData()->getDestGuid())->getGuid();

            // Evade means we are no longer in combat with this enemy, so we must drop aggro
            if ($combatLogEvent->getAdvancedData()->getInfoGuid() instanceof Evade) {
                $this->currentPull->forget($destGuid);
                $this->log->parseUnitEvadedRemovedFromCurrentPull($lineNr, $destGuid);

                return false;
            }

            // Check if this combat event is relevant and if it has a new NPC that we're interested in
            $newEnemyGuid = $newEnemyGuid ?? $this->hasGenericDataNewEnemy($combatLogEvent->getGenericData());
            if ($newEnemyGuid !== null) {
                // If it does we want to keep this event
                $this->currentPull->put($newEnemyGuid, $combatLogEvent);
                $this->log->parseUnitAddedToCurrentPull($lineNr, $newEnemyGuid);

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
     *
     * @return bool
     */
    private function isEnemyCombatLogEntry(BaseEvent $combatLogEvent): bool
    {
        // We skip all non-advanced combat log events, we need positional information of NPCs.
        if (!($combatLogEvent instanceof AdvancedCombatLogEvent)) {
            return false;
        }

        // Skip events that are not damage - they contain the location of the source (the player usually)
        if (!($combatLogEvent->getSuffix() instanceof Damage)) {// && !($combatLogEvent->getSuffix() instanceof CastSuccess)) {
            return false;
        }
        // Spells return the location of the source, not the target.
        // So for non-creatures (such as players) we don't care about them since they can be 0..40 yards off the mark
        // But if the source is the creature itself we ARE interested in everything it can throw at us. Unless they are a pet,
        // then the same rules apply as players since they effectively are players
        $sourceGuid = $combatLogEvent->getGenericData()->getSourceGuid();
        if (!($sourceGuid instanceof Creature) || $sourceGuid->getUnitType() === Creature::CREATURE_UNIT_TYPE_PET) {
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
     *
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

    /**
     * @param BaseEvent $combatLogEvent
     *
     * @return bool
     */
    private function hasDeathAuraApplied(BaseEvent $combatLogEvent): bool
    {
        return false;

        //        if (!($combatLogEvent instanceof CombatLogEvent)) {
        //            return false;
        //        }
        //        if (!($combatLogEvent->getSuffix() instanceof AuraApplied)) {
        //            return false;
        //        }
        //        $prefix = $combatLogEvent->getPrefix();
        //        if (!($prefix instanceof Spell)) {
        //            return false;
        //        }
        //
        //        return in_array($prefix->getSpellId(), [
        //            // Recovering... (Uldaman: Legacy of Tyr first boss(es))
        //            375339,
        //        ]);
    }

    /**
     * @param BaseEvent $combatLogEvent
     *
     * @return bool
     */
    private function isEnemyDefeated(BaseEvent $combatLogEvent): bool
    {
        if (!($combatLogEvent instanceof AdvancedCombatLogEvent)) {
            return false;
        }

        $destGuid = $combatLogEvent->getGenericData()->getDestGuid();
        if (!($destGuid instanceof Creature)) {
            return false;
        }

        if (!isset(self::DEFEATED_PERCENTAGE[$destGuid->getId()])) {
            return false;
        }

        $advancedData = $combatLogEvent->getAdvancedData();

        return ($advancedData->getCurrentHP() / $advancedData->getMaxHP()) <= self::DEFEATED_PERCENTAGE[$destGuid->getId()];
    }

    /**
     * @param string $guid
     *
     * @return AdvancedCombatLogEvent
     */
    private function getEnemyEngagedEvent(string $guid): AdvancedCombatLogEvent
    {
        /** @var AdvancedCombatLogEvent $engagedEvent */
        $engagedEvent = $this->currentPull->get($guid);

        /** @var AdvancedCombatLogEvent $firstSightedEvent */
        $firstSightedEvent = $this->firstEnemySightings->get($guid);

        if (MathUtils::distanceBetweenPoints(
                $engagedEvent->getAdvancedData()->getPositionX(),
                $firstSightedEvent->getAdvancedData()->getPositionX(),
                $engagedEvent->getAdvancedData()->getPositionY(),
                $firstSightedEvent->getAdvancedData()->getPositionY(),
            ) >= self::MAX_LEASH_DISTANCE) {
            $this->log->getEnemyEngagedEventUsingFirstSightedEvent($guid);

            return $firstSightedEvent;
        } else {
            $this->log->getEnemyEngagedEventUsingEngagedEvent($guid);

            return $engagedEvent;
        }
    }
}
