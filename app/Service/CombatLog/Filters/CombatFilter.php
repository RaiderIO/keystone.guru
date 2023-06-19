<?php

namespace App\Service\CombatLog\Filters;

use App\Logic\CombatLog\BaseEvent;
use App\Logic\CombatLog\CombatEvents\Advanced\AdvancedData;
use App\Logic\CombatLog\CombatEvents\AdvancedCombatLogEvent;
use App\Logic\CombatLog\CombatEvents\CombatLogEvent;
use App\Logic\CombatLog\CombatEvents\Suffixes\Summon;
use App\Logic\CombatLog\Guid\Creature;
use App\Logic\CombatLog\SpecialEvents\ChallengeModeEnd;
use App\Logic\CombatLog\SpecialEvents\ChallengeModeStart;
use App\Logic\CombatLog\SpecialEvents\UnitDied;
use App\Logic\Utils\MathUtils;
use App\Service\CombatLog\Interfaces\CombatLogParserInterface;
use App\Service\CombatLog\Logging\CurrentPullLoggingInterface;
use App\Service\CombatLog\ResultEvents\BaseResultEvent;
use App\Service\CombatLog\ResultEvents\EnemyEngaged;
use App\Service\CombatLog\ResultEvents\EnemyKilled;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;

class CombatFilter implements CombatLogParserInterface
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
        186121 => 0.05,
    ];

    /**
     * @var int The maximum distance since our absolute first sighting before we disregard any other events and return to the original distance.
     * If we aggroed an enemy using some spell (max range 40) and we proceed to drag the enemy 100 yards away before beating it to death, we're better
     * off taking the original position of the caster even if it's max 40 yards off. Better 40 yards than 100 yards.
     *
     * Also consider that if we max range pull an enemy and then proceed to pull that enemy in the direction away from the caster,
     * they will trigger this MAX_LEASH_DISTANCE quickly. However - the caster's location is even then probably a better reference
     * since we know that area is safe - be it from killed enemies or it's just clear, otherwise the player would be in combat already.
     * Using the target's current location to identify the enemy actually can resolve to an incorrect enemy quicker since it will
     * consider unpulled enemies quicker.
     */
    private const MAX_LEASH_DISTANCE = 40;

    /** @var Collection|BaseResultEvent[] */
    private Collection $resultEvents;

    /** @var Collection|int[] */
    private Collection $validNpcIds;

    /** @var Collection|CombatLogEvent[] List of GUID => CombatLogEvent for the absolute first sighting of an enemy. Used as a backup */
    private Collection $firstEnemySightings;

    /** @var Collection|CombatLogEvent[] List of GUID => CombatLogEvent for all enemies that we are currently in combat with. */
    private Collection $accurateEnemySightings;

    /** @var Collection|string[] List of GUIDs for all enemies that have been summoned. Summoned enemies are ignored by default. */
    private Collection $summonedEnemies;

    /** @var Collection|string[] List of GUIDs for all enemies that we have killed since the start. */
    private Collection $killedEnemies;

    /** @var bool */
    private bool $challengeModeStarted = false;

    /** @var CurrentPullLoggingInterface */
    protected $log;

    public function __construct(Collection $resultEvents)
    {
        $this->resultEvents           = $resultEvents;
        $this->validNpcIds            = collect();
        $this->firstEnemySightings    = collect();
        $this->accurateEnemySightings = collect();
        $this->summonedEnemies        = collect();
        $this->killedEnemies          = collect();

        /** @var CurrentPullLoggingInterface $log */
        $log       = App::make(CurrentPullLoggingInterface::class);
        $this->log = $log;
    }

    /**
     * @param Collection $validNpcIds
     */
    public function setValidNpcIds(Collection $validNpcIds): void
    {
        $this->validNpcIds = $validNpcIds;
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
            $this->accurateEnemySightings = collect();
            $this->challengeModeStarted   = true;

            return false;
        }

        // If it hasn't started yet, we don't process anything
        if (!$this->challengeModeStarted) {
            return false;
        }

        // If we ended it, stop all processing and drop combat of all enemies
        if ($combatLogEvent instanceof ChallengeModeEnd) {
            $this->log->parseChallengeModeEnded($lineNr);
            $this->accurateEnemySightings = collect();
            $this->challengeModeStarted   = false;

            return false;
        }

        // If a unit has died/is defeated
        if ($combatLogEvent instanceof UnitDied || $this->isEnemyDefeated($combatLogEvent) || $this->hasDeathAuraApplied($combatLogEvent)) {
            $destGuid = $combatLogEvent->getGenericData()->getDestGuid();
            $this->log->parseUnitDied($lineNr, $destGuid->getGuid());

            // And it's part of our current pull (it usually will be but doesn't have to be), and it also should not be killed already, AND also not summoned
            if (!$this->firstEnemySightings->has($destGuid->getGuid())) {
                $this->log->parseUnitDiedEnemyWasNotPartOfCurrentPull($lineNr, $destGuid->getGuid());

                return false;
            }

            if ($this->killedEnemies->search($destGuid->getGuid()) !== false) {
                $this->log->parseUnitDiedEnemyWasAlreadyKilled($lineNr, $destGuid->getGuid());

                return false;
            }

            if ($this->summonedEnemies->search($destGuid->getGuid()) !== false) {
                $this->log->parseUnitDiedEnemyWasSummoned($lineNr, $destGuid->getGuid());

                return false;
            }

            /** @var Creature $destGuid */
            if ($this->validNpcIds->search($destGuid->getId()) === false) {
                $this->log->parseUnitDiedInvalidNpcId($lineNr, $destGuid->getGuid());

                return false;
            }

            $enemyEngagedEvent = $this->getEnemyEngagedEvent($destGuid);
            if ($enemyEngagedEvent === null) {
                $this->log->parseUnitDiedEnemyWasNotEngaged($lineNr, $destGuid->getGuid());

                return false;
            }

            // Push a new result event - we successfully killed this enemy, and it gave count!
            $this->resultEvents->push((new EnemyEngaged($enemyEngagedEvent, $destGuid)));
            // Kill this enemy as well. We push as 2 separate events, so we can keep track of combat state
            $this->resultEvents->push((new EnemyKilled($combatLogEvent)));
            // Speed up parsing by getting rid of the accurate enemy sighting - it's part of killed enemies now so won't get handled anymore
            $this->accurateEnemySightings->forget($destGuid->getGuid());

            // We have officially killed this enemy
            $this->killedEnemies->push($destGuid);

            $this->log->parseUnitInCurrentPullKilled($lineNr, $destGuid->getGuid());

            return true;
        }

        $newEnemyGuid = null;
        if ($combatLogEvent instanceof CombatLogEvent) {
            if ($combatLogEvent instanceof AdvancedCombatLogEvent) {
                $newEnemyGuid = $this->hasAdvancedDataNewGuid($combatLogEvent->getAdvancedData());
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
            // Check if this combat event is relevant and if it has a new NPC that we're interested in
            $newEnemyGuid = $newEnemyGuid ?? $this->hasAdvancedDataNewGuid($combatLogEvent->getAdvancedData());
            if ($newEnemyGuid !== null) {
                // If it does we want to keep this event
                $this->accurateEnemySightings->put($newEnemyGuid, $combatLogEvent);
                $this->log->parseUnitAddedToCurrentPull($lineNr, $newEnemyGuid);

                return true;
            }
        }

        return false;
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

        // The info GUID is the GUID for which the advanced data is valid for
        // So if the info GUID is for an enemy we're 100% interested in this info
        $guid = $combatLogEvent->getAdvancedData()->getInfoGuid();
        return $guid instanceof Creature && $guid->getUnitType() !== Creature::CREATURE_UNIT_TYPE_PET;
    }

    /**
     * @param AdvancedData $advancedData
     *
     * @return string|null
     */
    private function hasAdvancedDataNewGuid(AdvancedData $advancedData): ?string
    {
        $guid = $advancedData->getInfoGuid();

        // We're not interested in events if they don't contain a creature
        if (!$guid instanceof Creature) {
            return null;
        }

        // Invalid NPC ID, ignore it since it can never be part of the route anyways
        if ($this->validNpcIds->search($guid->getId()) === false) {
            return null;
        }

        // We already killed this enemy - don't aggro it again (we may have dots from this enemy on our players)
        if ($this->killedEnemies->search($guid->getGuid()) !== false) {
            return null;
        }

        if ($this->accurateEnemySightings->has($guid->getGuid()) === false) {
            return $guid->getGuid();
        }

        return null;
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
     * Get the event where we first engaged the event. Be it the first time we actually saw this enemy
     * or the first time this enemy made its exact position known, based on
     *
     * @param string $guid
     *
     * @return AdvancedCombatLogEvent|null
     */
    private function getEnemyEngagedEvent(string $guid): ?AdvancedCombatLogEvent
    {
        /** @var AdvancedCombatLogEvent $engagedEvent */
        $engagedEvent = $this->accurateEnemySightings->get($guid);

        /** @var AdvancedCombatLogEvent $firstSightedEvent */
        $firstSightedEvent = $this->firstEnemySightings->get($guid);
        if ($engagedEvent === null) {
            return $firstSightedEvent;
        }

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
