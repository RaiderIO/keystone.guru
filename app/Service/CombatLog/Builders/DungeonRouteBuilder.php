<?php

namespace App\Service\CombatLog\Builders;

use App\Logic\Structs\IngameXY;
use App\Logic\Structs\LatLng;
use App\Models\DungeonKey;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Enemy;
use App\Models\EnemyPatrol;
use App\Models\Floor\Floor;
use App\Models\KillZone\KillZone;
use App\Models\KillZone\KillZoneEnemy;
use App\Repositories\Interfaces\DungeonRoute\DungeonRouteRepositoryInterface;
use App\Repositories\Interfaces\EnemyRepositoryInterface;
use App\Repositories\Interfaces\KillZone\KillZoneEnemyRepositoryInterface;
use App\Repositories\Interfaces\KillZone\KillZoneRepositoryInterface;
use App\Repositories\Interfaces\KillZone\KillZoneSpellRepositoryInterface;
use App\Repositories\Interfaces\Npc\NpcRepositoryInterface;
use App\Service\CombatLog\Builders\Logging\DungeonRouteBuilderLoggingInterface;
use App\Service\CombatLog\Builders\Rules\BossKillFloorCutoffRule;
use App\Service\CombatLog\Builders\Rules\DungeonRouteBuilderRuleInterface;
use App\Service\CombatLog\Builders\Rules\KingsRestDespawningEnemiesRule;
use App\Service\CombatLog\Builders\Rules\TempleOfSethralissDespawningEnemiesRule;
use App\Service\CombatLog\Builders\Rules\TheBlindingValeBridgeRule;
use App\Service\CombatLog\Models\ActivePull\ActivePull;
use App\Service\CombatLog\Models\ActivePull\ActivePullCollection;
use App\Service\CombatLog\Models\ActivePull\ActivePullEnemy;
use App\Service\CombatLog\Models\ClosestEnemy;
use App\Service\Coordinates\CoordinatesServiceInterface;
use Exception;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;

/**
 * Base builder that resolves NPC combat log events to Enemy entities using weighted spatial distance matching.
 */
abstract class DungeonRouteBuilder
{
    private const array DUNGEON_ENEMY_FLOOR_CHECK_ENABLED = [
        //        DungeonKey::THE_ROOKERY->value,
        //        DungeonKey::WAYCREST_MANOR->value
        //        DungeonKey::THEATER_OF_PAIN->value
    ];

    /**
     * @var array<int, class-string<DungeonRouteBuilderRuleInterface>> Per-dungeon exceptions to the normal spatial
     *                                                                 matching, for dungeons where geometry alone cannot tell two sets of enemies apart.
     *                                                                 Each rule decides for itself which dungeons it applies to - see the interface.
     */
    private const array RULES = [
        BossKillFloorCutoffRule::class,
        KingsRestDespawningEnemiesRule::class,
        TempleOfSethralissDespawningEnemiesRule::class,
        TheBlindingValeBridgeRule::class,
    ];

    protected const array NPC_ID_MAPPING = [
        // Brackenhide Gnolls transform into Witherlings after engaging them
        194373 => 187238,
    ];

    /** @var int The average HP of the current pull before we consider new enemies as part of a new pull */
    protected const CHAIN_PULL_DETECTION_HP_PERCENT = 30;

    /**
     * @var float Determines how heavy the kill priority skews the weight towards this enemy. A lower value means the pull is stronger.
     *            Note: this value is divided by 10 because the kill_priority high = 10, low = -10. Instead of dividing these values by 10,
     *            we divide this factor by 10. -0.50 means that a kill_priority of 10 the distance is multiplied by * 0.5 (so it appears much closer)
     *            and a kill_priority of -10 the distance is multiplied by * 1.5 (so it appears much further away).
     */
    private const ENEMY_KILL_PRIORITY_WEIGHT_RATIO = (-0.50 / 10);

    protected ?Floor $currentFloor;

    /** @var Collection<int, Enemy> */
    protected Collection $availableEnemies;

    /** @var ActivePullCollection<int, ActivePull>  */
    protected ActivePullCollection $activePullCollection;

    /** @var Collection<int, int> */
    protected Collection $validNpcIds;

    private int $killZoneIndex = 1;

    /** @var Collection<int, DungeonRouteBuilderRuleInterface> The rules active for this dungeon, if any */
    protected Collection $rules;

    /** @var Collection<int, KillZone> */
    protected Collection $killZones;

    public function __construct(
        protected CoordinatesServiceInterface                $coordinatesService,
        protected DungeonRouteRepositoryInterface            $dungeonRouteRepository,
        protected KillZoneRepositoryInterface                $killZoneRepository,
        protected KillZoneEnemyRepositoryInterface           $killZoneEnemyRepository,
        protected KillZoneSpellRepositoryInterface           $killZoneSpellRepository,
        protected EnemyRepositoryInterface                   $enemyRepository,
        protected NpcRepositoryInterface                     $npcRepository,
        protected DungeonRoute                               $dungeonRoute,
        private readonly DungeonRouteBuilderLoggingInterface $log,
    ) {
        $this->currentFloor     = null;
        $this->availableEnemies = $enemyRepository->getAvailableEnemiesForDungeonRouteBuilder($this->dungeonRoute->mappingVersion);

        // #1818 Filter out any NPC ids that are invalid
        $this->validNpcIds = $this->npcRepository->getInUseNpcIds($this->dungeonRoute->mappingVersion);

        $this->activePullCollection = new ActivePullCollection();

        // Instantiated per build - rules carry mutable state about how far into the run we are, so sharing them
        // between routes (as a singleton would under Octane) would leak that state across requests
        $this->rules = collect(self::RULES)
            ->map(static fn(string $rule): DungeonRouteBuilderRuleInterface => new $rule($log))
            ->filter(fn(DungeonRouteBuilderRuleInterface $rule) => $rule->appliesToDungeon($this->dungeonRoute->dungeon))
            ->values();

        // This allows me to set the killZones in buildFinished, so that existing relations are still preserved
        // If you don't Laravel probably starts resolving relations, and it will lose relations that were set
        // manually, which sucks when the repositories in these classes are actually Stubs
        $this->killZones = collect();
    }

    /**
     * @throws Exception
     */
    abstract public function build(): DungeonRoute;

    /**
     * The route this builder writes into - available before build() finishes, so a caller can clean it up when the
     * build throws halfway.
     */
    public function getDungeonRoute(): DungeonRoute
    {
        return $this->dungeonRoute;
    }

    /**
     * @return void
     */
    protected function buildFinished(): void
    {
        $this->dungeonRoute->setRelation('killZones', $this->killZones);

        // @TODO should this be done in the repository? Do we need to skip this for correction?
        // Direct update doesn't work.. no clue why
        $enemyForces = $this->dungeonRoute->getEnemyForces();
        $this->dungeonRouteRepository->find($this->dungeonRoute->id)->update(['enemy_forces' => $enemyForces]);
        $this->dungeonRoute->enemy_forces = $enemyForces;
    }

    protected function createPull(ActivePull $activePull): KillZone
    {
        try {
            $this->log->createPullStart($this->killZoneIndex);

            /** @var Collection<int, ActivePullEnemy> $killedEnemies */
            $killedEnemies = $activePull->getEnemiesKilled();

            $killZone = $this->killZoneRepository->create([
                'dungeon_route_id' => $this->dungeonRoute->id,
                'color'            => randomHexColorNoMapColors(),
                'index'            => $this->killZoneIndex,
            ]);

            // Keep track of which groups we're in combat with
            $killZoneEnemiesAttributes = collect();
            /** @var Collection<int, Enemy> $resolvedEnemies */
            $resolvedEnemies = collect();
            foreach ($killedEnemies as $guid => $killedEnemy) {
                /** @var string $guid */
                try {
                    $this->log->createPullFindEnemyForGuidStart($guid);

                    $enemy = $killedEnemy->getResolvedEnemy();

                    if ($enemy === null) {
                        $this->log->createPullEnemyNotFound(
                            $killedEnemy->getNpcId(),
                            $killedEnemy->getX(),
                            $killedEnemy->getY(),
                        );
                    } else {
                        // Schedule for creation later
                        $killZoneEnemiesAttributes->push([
                            'kill_zone_id' => $killZone->id,
                            'npc_id'       => $enemy->mdt_npc_id ?? $enemy->npc_id,
                            'mdt_id'       => $enemy->mdt_id,
                            'enemy_id'     => $enemy->id,
                        ]);
                        $resolvedEnemies->push($enemy);

                        $this->log->createPullEnemyAttachedToKillZone(
                            $killedEnemy->getNpcId(),
                            $killedEnemy->getX(),
                            $killedEnemy->getY(),
                        );
                    }
                } finally {
                    $this->log->createPullFindEnemyForGuidEnd();
                }
            }

            $killZone->setRelation('killZoneEnemies', $killZoneEnemiesAttributes->map(fn(array $attributes) => new KillZoneEnemy($attributes)));
            // StubRepository-backed builds hand out ids that collide with real kill_zone rows (#4178) - without this,
            // KillZone::getEnemies() would lazy-load the 'enemies' relation and silently query an unrelated route's
            // enemies by that colliding id the moment anything calls getKillLocation()/getEnemies() on this pull.
            $killZone->setRelation('enemies', new EloquentCollection($resolvedEnemies->values()));

            if ($killZoneEnemiesAttributes->isNotEmpty()) {
                $this->killZoneEnemyRepository->insert($killZoneEnemiesAttributes->toArray());
                $this->killZoneIndex++;
                $enemyCount = $killZoneEnemiesAttributes->count();
                $this->log->createPullInsertedEnemies($enemyCount);

                // Assign spells to the pull
                $killZoneSpellsAttributes = collect();
                foreach ($activePull->getSpellsCast() as $spellId) {
                    $killZoneSpellsAttributes->push([
                        'kill_zone_id' => $killZone->id,
                        'spell_id'     => $spellId,
                    ]);
                }

                if ($killZoneSpellsAttributes->isNotEmpty()) {
                    $this->killZoneSpellRepository->insert($killZoneSpellsAttributes->toArray());
                    $spellCount = $killZoneSpellsAttributes->count();
                    $this->log->createPullSpellsAttachedToKillZone($killZone->id, $killZoneSpellsAttributes->pluck('spell_id')->toArray(), $spellCount);
                }

                $this->killZones->push($killZone);
            } else {
                // No enemies were inserted for this pull, so it's worthless. Delete it
                $this->killZoneRepository->delete($killZone);
                $this->log->createPullNoEnemiesPullDeleted();
            }
        } finally {
            $this->log->createPullEnd();
        }

        return $killZone;
    }

    /**
     * @param Collection<int, bool> $preferredGroups  The groups that are pulled and should always be preferred when choosing enemies
     * @param bool                  $ignoreRangeCheck Match regardless of how far the closest candidate is, for a kill whose
     *                                                location is synthetic rather than logged - see awardEnemyKills().
     */
    protected function findUnkilledEnemyForNpcAtIngameLocation(
        ActivePullEnemy $activePullEnemy,
        Collection      $preferredGroups,
        bool            $ignoreRangeCheck = false,
    ): ?Enemy {
        // See if we actually need to go look for another NPC
        if (isset(self::NPC_ID_MAPPING[$activePullEnemy->getNpcId()])) {
            $this->log->findUnkilledEnemyForNpcAtIngameLocationMappingToDifferentNpcId(
                $activePullEnemy->getNpcId(),
                self::NPC_ID_MAPPING[$activePullEnemy->getNpcId()],
            );
            $npcId = self::NPC_ID_MAPPING[$activePullEnemy->getNpcId()];
        } else {
            $npcId = $activePullEnemy->getNpcId();
        }

        try {
            $this->log->findUnkilledEnemyForNpcAtIngameLocationStart(
                $npcId,
                $activePullEnemy->getX(),
                $activePullEnemy->getY(),
                $preferredGroups->toArray(),
            );

            // Find the closest Enemy with the same NPC ID that is not killed yet
            $closestEnemy = $this->findClosestEnemyForNpcId(
                $npcId,
                $activePullEnemy,
                $preferredGroups,
                true,
                $ignoreRangeCheck,
            );

            // A first pass exclusion would otherwise drop this enemy from the route - and from its enemy forces -
            // entirely. Rather than lose it, fall back to matching without those exclusions: the worst case is then
            // the behaviour we had before the rule existed. Hard exclusions (isEnemyEligible) still apply.
            if ($closestEnemy->getEnemy() === null && $this->rules->contains(
                static fn(DungeonRouteBuilderRuleInterface $rule) => $rule->hasActiveFirstPassExclusion(),
            )) {
                $this->log->findUnkilledEnemyForNpcAtIngameLocationRetryingWithoutFirstPassExclusions($npcId);

                $closestEnemy = $this->findClosestEnemyForNpcId(
                    $npcId,
                    $activePullEnemy,
                    $preferredGroups,
                    false,
                    $ignoreRangeCheck,
                );
            }

            if ($closestEnemy->getEnemy() !== null) {
                $this->log->findUnkilledEnemyForNpcAtIngameLocationEnemyFound(
                    $closestEnemy->getEnemy()->id,
                    $closestEnemy->getDistanceBetweenEnemies(),
                );

                $this->availableEnemies->forget($closestEnemy->getEnemy()->id);
            }
        } finally {
            $this->log->findUnkilledEnemyForNpcAtIngameLocationEnd();
        }

        return $closestEnemy->getEnemy();
    }

    /**
     * Runs all three matching strategies - preferred groups, preferred floor, everything - over the unkilled enemies
     * carrying the given NPC id.
     *
     * @param Collection<int, bool> $preferredGroups
     */
    private function findClosestEnemyForNpcId(
        int             $npcId,
        ActivePullEnemy $activePullEnemy,
        Collection      $preferredGroups,
        bool            $applyFirstPassExclusions,
        bool            $ignoreRangeCheck = false,
    ): ClosestEnemy {
        $closestEnemy = new ClosestEnemy();

        /** @var Collection<int, Enemy> $filteredEnemies */
        $filteredEnemies = $this->availableEnemies->filter(function (Enemy $availableEnemy) use ($npcId, $applyFirstPassExclusions) {
            if ($availableEnemy->npc_id !== $npcId) {
                return false;
            }

            // Just ignore all Teeming enemies - Teeming is removed
            if ($availableEnemy->teeming !== null) {
                return false;
            }

            // Floor checks are a nice idea but in practice they don't work because Blizzard does not take floors
            // as seriously as we do. For just about every dungeon there are enemies on the wrong floors after which
            // I have to exclude them in the below check, but every dungeon has these issues, so we simply cannot do this.
            // Annoying, but that's what it is.
            if (in_array($availableEnemy->floor->dungeon->key, self::DUNGEON_ENEMY_FLOOR_CHECK_ENABLED) &&
                $availableEnemy->floor_id !== $this->currentFloor->id) {
                return false;
            }

            // Per-dungeon rules get the final say - a hard exclusion always applies, a first pass exclusion only
            // until the retry above gives up on finding anything better.
            foreach ($this->rules as $rule) {
                if (!$rule->isEnemyEligible($availableEnemy)) {
                    return false;
                }

                if ($applyFirstPassExclusions && !$rule->isEnemyEligibleOnFirstPass($availableEnemy)) {
                    return false;
                }
            }

            return true;
        });

        $this->findClosestEnemyInPreferredGroups(
            $preferredGroups,
            $filteredEnemies,
            $activePullEnemy,
            $closestEnemy,
        );

        if ($closestEnemy->getEnemy() !== null) {
            // If we found an enemy in one of our preferred packs, we must not continue searching
            $this->log->findUnkilledEnemyForNpcAtIngameLocationEnemyFoundInPreferredGroup(
                $closestEnemy->getEnemy()->id,
                $closestEnemy->getDistanceBetweenEnemies(),
                $closestEnemy->getEnemy()->enemyPack->group,
            );
        } elseif (in_array($this->dungeonRoute->dungeon->key, self::DUNGEON_ENEMY_FLOOR_CHECK_ENABLED)) {
            $this->findClosestEnemyInPreferredFloor(
                $filteredEnemies,
                $activePullEnemy,
                $closestEnemy,
            );
        }

        if ($closestEnemy->getEnemy() !== null) {
            // If we found an enemy on our preferred floor, we must not continue searching
            $this->log->findUnkilledEnemyForNpcAtIngameLocationEnemyFoundInPreferredFloor(
                $closestEnemy->getEnemy()->id,
                $closestEnemy->getDistanceBetweenEnemies(),
                $closestEnemy->getEnemy()->floor_id,
            );
        } else {
            $this->findClosestEnemyInAllFilteredEnemies(
                $filteredEnemies,
                $activePullEnemy,
                $closestEnemy,
                $ignoreRangeCheck,
            );
        }

        return $closestEnemy;
    }

    /**
     * Lets every active rule advance its state now that an enemy has died.
     *
     * Both builders call this, so a rule applies to a route built from what Raider.IO sends us and to one built from
     * a combat log we parsed ourselves alike (#4275). The two paths differ in what they can award off a death:
     * ResultEventDungeonRouteBuilder only knows a killed enemy's position when it saw it engaged first.
     *
     * Takes the npc_id as it was logged alongside the resolved Enemy, because a boss that failed to resolve to a
     * mapped enemy would otherwise never advance a rule that keys off it dying.
     *
     * @return Collection<int, int> The npc_ids the rules want a kill awarded for - see awardEnemyKills()
     */
    protected function notifyRulesEnemyDied(?int $npcId, ?Enemy $resolvedEnemy): Collection
    {
        /** @var Collection<int, int> $awardedNpcIds */
        $awardedNpcIds = collect();

        if ($npcId === null) {
            return $awardedNpcIds;
        }

        foreach ($this->rules as $rule) {
            $awardedNpcIds = $awardedNpcIds->merge($rule->onEnemyDied($npcId, $resolvedEnemy));
        }

        return $awardedNpcIds;
    }

    /**
     * Attaches kills to $activePull for enemies that despawn instead of dying so that their death never reaches us at
     * all. See DungeonRouteBuilderRuleInterface::onEnemyDied().
     *
     * An awarded npc_id awards *every* one of its remaining mapped enemies, not one of them - the npc despawned as a
     * group, so crediting a single placement would leave the rest of it out of the route and its enemy forces.
     *
     * The awarded kill borrows the position and timing of the enemy whose death triggered it, since that is the only
     * thing we know about it. That location is therefore synthetic, and the normal engagement range gate - which
     * exists to catch mismatches in real positional data - would reject anything the trigger did not happen to die
     * next to. So range is ignored here; the npc_id is what identifies the enemy.
     *
     * @param Collection<int, int> $npcIds
     */
    protected function awardEnemyKills(Collection $npcIds, ?ActivePull $activePull, ActivePullEnemy $trigger): void
    {
        // The trigger is only part of a pull if it resolved to a mapped enemy when it was engaged. It may not have,
        // and the kills still have to land somewhere - so fall back to the pull in progress, or start one.
        $activePull ??= $this->activePullCollection->last() ?? $this->activePullCollection->addNewPull();

        foreach ($npcIds as $npcId) {
            $awardedCount = 0;

            // findUnkilledEnemyForNpcAtIngameLocation() forgets each enemy it returns, so this walks the npc's
            // remaining mapped enemies and terminates on its own once they are exhausted.
            while (true) {
                $awardedEnemy = new ActivePullEnemy(
                    sprintf('%s-awarded-%d-%d', $trigger->getUniqueId(), $npcId, $awardedCount),
                    $npcId,
                    $trigger->getX(),
                    $trigger->getY(),
                    $trigger->getEngagedAt(),
                    $trigger->getDiedAt() ?? $trigger->getEngagedAt(),
                );

                $resolvedEnemy = $this->findUnkilledEnemyForNpcAtIngameLocation(
                    $awardedEnemy,
                    $this->activePullCollection->getInCombatGroups(),
                    true,
                );

                if ($resolvedEnemy === null) {
                    break;
                }

                $awardedEnemy->setResolvedEnemy($resolvedEnemy);

                // Engaged and killed in one go - we have no timeline for it, only the fact that it is dead
                $activePull->enemyEngaged($awardedEnemy);
                $activePull->enemyKilled($awardedEnemy->getUniqueId());

                $this->log->awardEnemyKillsEnemyAwarded($npcId, $resolvedEnemy->id);

                $awardedCount++;
            }

            if ($awardedCount === 0) {
                $this->log->awardEnemyKillsEnemyNotFound($npcId);
            }
        }
    }

    /**
     * If we're looking for the closest enemy for an active pull, check if we can find a matching enemy in an already
     * engaged pack.
     * @param Collection<int, bool>  $preferredGroups
     * @param Collection<int, Enemy> $filteredEnemies
     */
    private function findClosestEnemyInPreferredGroups(
        Collection      $preferredGroups,
        Collection      $filteredEnemies,
        ActivePullEnemy $activePullEnemy,
        ClosestEnemy    $closestEnemy,
    ): void {
        try {
            $this->log->findClosestEnemyInPreferredGroupsStart($preferredGroups->toArray());

            // Build a list of potential enemies which will always take precedence since they're in a group that we have aggroed.
            // Therefore, these enemies should be in combat with us regardless
            /** @var Collection<int, Enemy> $preferredEnemiesInEngagedGroups */
            $preferredEnemiesInEngagedGroups = $filteredEnemies->filter(static fn(Enemy $availableEnemy) => $availableEnemy->enemy_pack_id !== null && $preferredGroups->has($availableEnemy->enemyPack->group));

            if ($preferredEnemiesInEngagedGroups->isNotEmpty()) {
                $this->findClosestEnemyAndDistanceFromList(
                    $preferredEnemiesInEngagedGroups,
                    $activePullEnemy,
                    $closestEnemy,
                );
            }
        } finally {
            $this->log->findClosestEnemyInPreferredGroupsEnd();
        }
    }

    /**
     * Check if we can find an enemy on our preferred floor first. If we cannot find it, only then consider enemies
     * on other floors.
     * @param Collection<int, Enemy> $filteredEnemies
     */
    private function findClosestEnemyInPreferredFloor(
        Collection      $filteredEnemies,
        ActivePullEnemy $activePullEnemy,
        ClosestEnemy    $closestEnemy,
    ): void {
        try {
            $this->log->findClosestEnemyInPreferredFloorStart($this->currentFloor->id);

            /** @var Collection<int, Enemy> $preferredEnemiesOnCurrentFloor */
            $preferredEnemiesOnCurrentFloor = $filteredEnemies->filter(
                fn(Enemy $availableEnemy) => // Only if we have floor checks enabled for this dungeon
                    $availableEnemy->floor_id == $this->currentFloor->id,
            );

            if ($preferredEnemiesOnCurrentFloor->isNotEmpty()) {
                $this->findClosestEnemyAndDistanceFromList(
                    $preferredEnemiesOnCurrentFloor,
                    $activePullEnemy,
                    $closestEnemy,
                );
            }
        } finally {
            $this->log->findClosestEnemyInPreferredFloorEnd();
        }
    }

    /**
     * If we cannot find an enemy with any other criteria, just consider them all instead.
     * @param Collection<int, Enemy> $filteredEnemies
     */
    private function findClosestEnemyInAllFilteredEnemies(
        Collection      $filteredEnemies,
        ActivePullEnemy $activePullEnemy,
        ClosestEnemy    $closestEnemy,
        bool            $ignoreRangeCheck = false,
    ): void {
        try {
            $this->log->findClosestEnemyInAllFilteredEnemiesStart();

            // There's only one boss NPC in the mapping, so killing it unambiguously matches it regardless of how
            // far its death location is from where it's mapped - let it be matched regardless of range. Non-boss
            // candidates are still rejected by range inside findClosestEnemyAndDistance() itself, before they're
            // ever recorded as the closest enemy - unless the caller already told us the location is synthetic.
            $ignoreRangeCheck = $ignoreRangeCheck || ($filteredEnemies->first()?->npc?->isBoss() ?? false);

            $this->findClosestEnemyAndDistanceFromList(
                $filteredEnemies,
                $activePullEnemy,
                $closestEnemy,
                false,
                $ignoreRangeCheck,
            );

            // If the closest enemy was still pretty far away - check if there was a patrol that may have been closer
            if ($closestEnemy->getDistanceBetweenEnemies() >
                ($this->currentFloor->enemy_engagement_max_range_patrols ?? config('keystoneguru.enemy_engagement_max_range_patrols_default'))) {
                $this->findClosestEnemyAndDistanceFromList(
                    $filteredEnemies,
                    $activePullEnemy,
                    $closestEnemy,
                    true,
                    $ignoreRangeCheck,
                );
            }

            if ($closestEnemy->getEnemy() === null) {
                $this->log->findClosestEnemyInAllFilteredEnemiesEnemyIsNull(
                    $closestEnemy->getDistanceBetweenEnemies(),
                );
            } elseif ($closestEnemy->getDistanceBetweenEnemies() >
                ($this->currentFloor->enemy_engagement_max_range ?? config('keystoneguru.enemy_engagement_max_range_default'))) {
                if ($ignoreRangeCheck) {
                    $this->log->findClosestEnemyInAllFilteredEnemiesEnemyIsBossIgnoringTooFarAwayCheck();
                } else {
                    $this->log->findClosestEnemyInAllFilteredEnemiesEnemyTooFarAway(
                        $closestEnemy->getEnemy()->id,
                        $closestEnemy->getDistanceBetweenEnemies(),
                        $this->currentFloor->enemy_engagement_max_range ?? config('keystoneguru.enemy_engagement_max_range_default'),
                    );

                    $closestEnemy->setEnemy(null);
                }
            }
        } finally {
            $this->log->findClosestEnemyInAllFilteredEnemiesEnd();
        }
    }

    /**
     * @param Collection<int, Enemy> $enemies
     */
    private function findClosestEnemyAndDistanceFromList(
        Collection      $enemies,
        ActivePullEnemy $enemy,
        ClosestEnemy    $closestEnemy,
        bool            $considerPatrols = false,
        bool            $ignoreRangeCheck = false,
    ): bool {
        $result = false;

        $this->log->findClosestEnemyAndDistanceFromList($enemies->count(), $considerPatrols);

        // For each group of enemies
        foreach ($enemies as $availableEnemy) {
            if ($considerPatrols) {
                if (!($availableEnemy->enemyPatrol instanceof EnemyPatrol)) {
                    continue;
                }

                // If this enemy is part of a patrol, consider all patrol vertices as a location of this enemy as well.
                $vertices = $availableEnemy->enemyPatrol->polyline->getDecodedLatLngs($availableEnemy->floor);

                foreach ($vertices as $latLng) {
                    $foundNewClosestEnemy = $this->findClosestEnemyAndDistance(
                        $availableEnemy,
                        $latLng,
                        $enemy->getIngameXY(),
                        $closestEnemy,
                        $ignoreRangeCheck,
                    );
                    $result = $result || $foundNewClosestEnemy;
                }
            } else {
                $foundNewClosestEnemy = $this->findClosestEnemyAndDistance(
                    $availableEnemy,
                    $availableEnemy->getLatLng(),
                    $enemy->getIngameXY(),
                    $closestEnemy,
                    $ignoreRangeCheck,
                );
                $result = $result || $foundNewClosestEnemy;
            }
        }

        $this->log->findClosestEnemyAndDistanceFromListResult(
            $closestEnemy->getEnemy()?->id,
            $closestEnemy->getDistanceBetweenEnemies(),
        );

        return $result;
    }

    /**
     * @return bool True if an enemy close enough was found
     */
    private function findClosestEnemyAndDistance(
        Enemy        $availableEnemy,
        LatLng       $enemyLatLng,
        IngameXY     $targetIngameXY,
        ClosestEnemy $closestEnemy,
        bool         $ignoreRangeCheck = false,
    ): bool {
        $result = false;

        // Always use the floor that the enemy itself is on, not $this->currentFloor
        $enemyXY                = $this->coordinatesService->calculateIngameLocationForMapLocation($enemyLatLng);
        $distanceBetweenEnemies = $this->coordinatesService->distanceBetweenPoints(
            $enemyXY->getX(),
            $targetIngameXY->getX(),
            $enemyXY->getY(),
            $targetIngameXY->getY(),
        );

        // $this->log->findClosestEnemyAndDistanceDistanceBetweenEnemies($enemyXY->toArray(), $targetIngameXY->toArray(), $distanceBetweenEnemies, $closestEnemy->getDistanceBetweenEnemies());

        if ($ignoreRangeCheck || $distanceBetweenEnemies < ($this->currentFloor->enemy_engagement_max_range ?? config('keystoneguru.enemy_engagement_max_range_default'))) {
            // Skew the distance by the enemy's kill priority so a high priority enemy appears closer than it really is
            $weightedTotalDistance = $distanceBetweenEnemies *
                (1 + ($availableEnemy->kill_priority * self::ENEMY_KILL_PRIORITY_WEIGHT_RATIO));

            // If this yields a "distance" closer than the enemy we had before, we found a "closer" enemy.
            if ($closestEnemy->getWeightedTotalDistance() > $weightedTotalDistance) {
                $closestEnemy->setEnemy($availableEnemy);
                $closestEnemy->setDistanceBetweenEnemies($distanceBetweenEnemies);
                $closestEnemy->setWeightedTotalDistance($weightedTotalDistance);

                $result = true;
            }
        }

        return $result;
    }
}
