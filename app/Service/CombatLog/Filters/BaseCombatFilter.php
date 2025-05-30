<?php

namespace App\Service\CombatLog\Filters;

use App\Logic\CombatLog\BaseEvent;
use App\Logic\CombatLog\CombatEvents\Advanced\AdvancedDataInterface;
use App\Logic\CombatLog\CombatEvents\AdvancedCombatLogEvent;
use App\Logic\CombatLog\CombatEvents\CombatLogEvent;
use App\Logic\CombatLog\CombatEvents\Suffixes\Summon;
use App\Logic\CombatLog\Guid\Creature;
use App\Logic\CombatLog\Guid\Player;
use App\Logic\CombatLog\SpecialEvents\EncounterEnd\EncounterEndInterface;
use App\Logic\CombatLog\SpecialEvents\UnitDied;
use App\Models\Npc\Npc;
use App\Service\CombatLog\Filters\Logging\BaseCombatFilterLoggingInterface;
use App\Service\CombatLog\Interfaces\CombatLogParserInterface;
use App\Service\CombatLog\ResultEvents\BaseResultEvent;
use App\Service\CombatLog\ResultEvents\EnemyEngaged;
use App\Service\CombatLog\ResultEvents\EnemyKilled;
use App\Service\CombatLog\ResultEvents\PlayerDied;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;

/**
 * @property Collection<BaseResultEvent> $resultEvents
 */
abstract class BaseCombatFilter implements CombatLogParserInterface
{
    /** @var float[] The percentage (between 0 and 1) when certain enemies are considered defeated */
    private const DEFEATED_PERCENTAGE = [
        // Grim Batol: Valiona is defeated at 50%
        40320  => 0.51,

        // Siege of Boralus: Viq'Goth flails around, disappears in the deep at 1 hp and leaves a chest
        128652 => 0.01,

        // Mists of Tirna Scithe: Tirnenn Villager is defeated at like 20%?
        164929 => 0.20,
        // Mistveil Defenders just despawn when defeated
        163058 => 0.01,
        171772 => 0.01,

        // Uldaman: Lost Dwarves, they kinda stop fighting when they reach below 10%
        184580 => 0.1,
        184581 => 0.1,
        184582 => 0.1,

        // Uldaman: Chrono-Lord Deios goes "ENOUGH" at 1hp, makes himself immune and teleports away
        184125 => 0.01,

        // Brackenhide Hollow: Decatriarch Wratheye is defeated at 5%
        186121 => 0.05,

        // DOTI: Galakrond's Fall: Iridikron gets zapped at 85%
        198933 => 0.85,

        // City of Threads: Xeph'itik is defeated at 50%
        219984 => 0.51,

        // Darkflame Cleft: The Darkness is defeated at 45%
        208747 => 0.46,
    ];

    /** @var array Some enemies are summoned that we DO want to track in the route */
    private const SUMMONED_NPC_ID_WHITELIST = [
        // Vexamus, Algeth'ar Academy is a boss that gets summoned
        194181,
    ];

    /** @var Collection<int> A list of valid NPC IDs, any NPCs not in this list will be discarded. */
    private Collection $validNpcIds;

    /** @var Collection<CombatLogEvent> List of GUID => CombatLogEvent for all enemies that we are currently in combat with. */
    private readonly Collection $accurateEnemySightings;

    /** @var Collection<CombatLogEvent> List of GUID => CombatLogEvent for all player's last known positions. */
    private readonly Collection $lastKnownPlayerPositions;

    /** @var Collection<string> List of GUIDs for all enemies that have been summoned. Summoned enemies are ignored by default. */
    private readonly Collection $summonedEnemies;

    /** @var Collection<string> List of GUIDs for all enemies that we have killed since the start. */
    private readonly Collection $killedEnemies;

    private readonly BaseCombatFilterLoggingInterface $log;

    public function __construct(private readonly Collection $resultEvents)
    {
        $this->validNpcIds              = collect();
        $this->accurateEnemySightings   = collect();
        $this->lastKnownPlayerPositions = collect();
        $this->summonedEnemies          = collect();
        $this->killedEnemies            = collect();

        /** @var BaseCombatFilterLoggingInterface $log */
        $log       = App::make(BaseCombatFilterLoggingInterface::class);
        $this->log = $log;
    }

    public function setValidNpcIds(Collection $validNpcIds): void
    {
        $this->validNpcIds = $validNpcIds;
    }

    public function parse(BaseEvent $combatLogEvent, int $lineNr): bool
    {
        // If a unit has died/is defeated
        if ($combatLogEvent instanceof UnitDied || $this->isEnemyDefeated($combatLogEvent) || $this->hasDeathAuraApplied($combatLogEvent)) {
            if ($this->isPlayerDeathEntry($combatLogEvent)) {
                /** @var UnitDied $combatLogEvent */
                /** @var Player $playerGuid */
                $playerGuid = $combatLogEvent->getGenericData()->getDestGuid();
                $this->resultEvents->push(
                    new PlayerDied(
                        $combatLogEvent,
                        $playerGuid,
                        $this->lastKnownPlayerPositions->get($playerGuid->getGuid())
                    )
                );
                $this->log->parsePlayerDeath($lineNr, $playerGuid);

                return true;
            }

            // If an encounter was ended, then yes this enemy was defeated
            if ($combatLogEvent instanceof EncounterEndInterface) {
                // Find the NPC that was part of this encounter
                $npc = Npc::firstWhere('encounter_id', $combatLogEvent->getEncounterId());

                if ($npc !== null) {
                    // Npc was found, now retrieve the relevant GUID of the boss
                    /** @var CombatLogEvent $enemyEngagedEvent */
                    $enemyEngagedEvent = $this->accurateEnemySightings->first(function ($value, $key) use ($npc) {
                        return str_contains($key, $npc->id);
                    });

                    if ($enemyEngagedEvent !== null) {
                        // Found, now construct the GUID and continue as if this was a normal death
                        $destGuid = $enemyEngagedEvent->getGenericData()->getDestGuid();

                        $this->log->parseEncounterEndBossFoundAndKilled($lineNr, $destGuid->getGuid());
                    } else {
                        $this->log->parseEncounterEndNpcNotInCombat($lineNr, $npc->id);

                        return false;
                    }
                } else {
                    $this->log->parseEncounterEndNoNpc($lineNr);

                    return false;
                }

            } else {
                $destGuid = $combatLogEvent->getGenericData()->getDestGuid();
                $this->log->parseUnitDied($lineNr, $destGuid->getGuid());
            }

            // And it's part of our current pull (it usually will be but doesn't have to be), and it also should not be killed already, AND also not summoned
            if (!$this->accurateEnemySightings->has($destGuid->getGuid())) {
                $this->log->parseEnemyWasNotPartOfCurrentPull($lineNr, $destGuid->getGuid());

                return false;
            }

            if ($this->killedEnemies->search($destGuid->getGuid()) !== false) {
                $this->log->parseEnemyWasAlreadyKilled($lineNr, $destGuid->getGuid());

                return false;
            }

            if ($this->summonedEnemies->search($destGuid->getGuid()) !== false) {
                $this->log->parseEnemyWasSummoned($lineNr, $destGuid->getGuid());

                return false;
            }

            /** @var Creature $destGuid */
            if ($this->validNpcIds->isNotEmpty() && $this->validNpcIds->search($destGuid->getId()) === false) {
                $this->log->parseInvalidNpcId($lineNr, $destGuid->getGuid());

                return false;
            }

            $enemyEngagedEvent = $this->accurateEnemySightings->get($destGuid->getGuid());
            if ($enemyEngagedEvent === null) {
                $this->log->parseEnemyWasNotEngaged($lineNr, $destGuid->getGuid());

                return false;
            }

            // Push a new result event - we successfully killed this enemy, and it gave count!
            $this->resultEvents->push((new EnemyEngaged($enemyEngagedEvent)));
            // Kill this enemy as well. We push as 2 separate events, so we can keep track of combat state
            $this->resultEvents->push((new EnemyKilled($combatLogEvent, $destGuid)));
            // Speed up parsing by getting rid of the accurate enemy sighting - it's part of killed enemies now so won't get handled anymore
            $this->accurateEnemySightings->forget($destGuid->getGuid());

            // We have officially killed this enemy
            $this->killedEnemies->push($destGuid);

            $this->log->parseUnitInCurrentPullKilled($lineNr, $destGuid->getGuid());

            return true;
        }

        // Keep player position data up-to-date
        if ($this->isPlayerCombatLogEntry($combatLogEvent)) {
            /** @var AdvancedCombatLogEvent $combatLogEvent */
            $sourceGuid = $combatLogEvent->getGenericData()->getSourceGuid();
            $this->lastKnownPlayerPositions->put($sourceGuid->getGuid(), $combatLogEvent);
        }

        if ($combatLogEvent instanceof CombatLogEvent) {
            if ($combatLogEvent->getSuffix() instanceof Summon) {
                $destGuid = $combatLogEvent->getGenericData()->getDestGuid();
                if ($destGuid instanceof Creature && in_array($destGuid->getId(), self::SUMMONED_NPC_ID_WHITELIST)) {
                    $this->log->parseUnitSummonedInWhitelist($lineNr, $destGuid->getGuid());
                } else {
                    // Specially handle summoned enemies
                    $this->summonedEnemies->push($destGuid->getGuid());
                    $this->log->parseUnitSummoned($lineNr, $destGuid->getGuid());

                    return false;
                }
            }
        }

        // Ignore all irrelevant non-combat events going forward
        if ($this->isEnemyCombatLogEntry($combatLogEvent)) {
            /** @var AdvancedCombatLogEvent $combatLogEvent */
            // Check if this combat event is relevant and if it has a new NPC that we're interested in
            $newEnemyGuid = $this->hasAdvancedDataNewGuid($combatLogEvent->getAdvancedData());
            if ($newEnemyGuid !== null) {
                // If it does, we want to keep this event
                $this->accurateEnemySightings->put($newEnemyGuid, $combatLogEvent);
                $this->log->parseUnitAddedToCurrentPull($lineNr, $newEnemyGuid);

                return true;
            }
        }

        return false;
    }

    private function isPlayerDeathEntry(BaseEvent $combatLogEvent): bool
    {
        if (!($combatLogEvent instanceof UnitDied)) {
            return false;
        }

        return $combatLogEvent->getGenericData()->getDestGuid() instanceof Player;
    }

    private function isPlayerCombatLogEntry(BaseEvent $combatLogEvent): bool
    {
        // We skip all non-advanced combat log events, we need positional information of players.
        return $combatLogEvent instanceof AdvancedCombatLogEvent &&
            $combatLogEvent->getGenericData()->getSourceGuid() instanceof Player;
    }

    private function isEnemyCombatLogEntry(BaseEvent $combatLogEvent): bool
    {
        // We skip all non-advanced combat log events, we need positional information of NPCs.
        if (!($combatLogEvent instanceof AdvancedCombatLogEvent)) {
            return false;
        }

        $sourceGuid = $combatLogEvent->getGenericData()->getSourceGuid();

        // If it IS a pet/vehicle we want to accept the event. Vehicles in case players mount up on a creature and
        // blast enemies from above, for example. Grim Batol is one such example.
        $whitelistedUnitTypes = [
            Creature::CREATURE_UNIT_TYPE_PET,
            Creature::CREATURE_UNIT_TYPE_VEHICLE,
        ];

        $whitelistedNpcIds = [
            128652, // Siege of Boralus: Viq'Goth, he damages himself when you fire a cannonball. We want to track this.
        ];

        if ($sourceGuid instanceof Creature &&
            !in_array($sourceGuid->getId(), $whitelistedNpcIds) &&
            !in_array($sourceGuid->getUnitType(), $whitelistedUnitTypes)) {
            // Ignore creature-on-creature events, such as an enemy empowering another. But make an exception if
            // the target was a pet - creatures attacking a pet should still register
            $destGuid = $combatLogEvent->getGenericData()->getDestGuid();
            // If dest is null it may be a self buff - ignore these (we may not be in combat with them)
            if ($destGuid === null ||
                ($destGuid instanceof Creature && !in_array($destGuid->getUnitType(), $whitelistedUnitTypes))) {
                return false;
            }
        }

        return true;

        //        // We skip all non-advanced combat log events, we need positional information of NPCs.
        //        if (!($combatLogEvent instanceof AdvancedCombatLogEvent)) {
        //            return false;
        //        }
        //
        //        // The info GUID is the GUID for which the advanced data is valid for
        //        // So if the info GUID is for an enemy we're 100% interested in this info
        //        $infoGuid = $combatLogEvent->getAdvancedData()->getInfoGuid();
        //        // Ignore events that did not originate from
        //        return !($combatLogEvent->getGenericData()->getSourceGuid() instanceof Creature) &&
        //            $infoGuid instanceof Creature &&
        //            $infoGuid->getUnitType() !== Creature::CREATURE_UNIT_TYPE_PET;
    }

    private function hasAdvancedDataNewGuid(AdvancedDataInterface $advancedData): ?string
    {
        $guid = $advancedData->getInfoGuid();

        // We're not interested in events if they don't contain a creature
        if (!$guid instanceof Creature) {
            return null;
        }

        // Invalid NPC ID, ignore it since it can never be part of the route anyways
        if ($this->validNpcIds->isNotEmpty() && $this->validNpcIds->search($guid->getId()) === false) {
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

    private function isEnemyDefeated(BaseEvent $combatLogEvent): bool
    {
        // If an encounter was ended, then yes this enemy was defeated
        if ($combatLogEvent instanceof EncounterEndInterface) {
            return true;
        }

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

        // Assume the enemy is immortal? (e.g. a boss that doesn't have a max HP)
        if ($advancedData->getMaxHP() === 0) {
            return false;
        }

        return ($advancedData->getCurrentHP() / $advancedData->getMaxHP()) <= self::DEFEATED_PERCENTAGE[$destGuid->getId()];
    }
}
