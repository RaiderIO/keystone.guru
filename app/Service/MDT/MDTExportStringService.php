<?php

namespace App\Service\MDT;

use App\Logic\MDT\Conversion;
use App\Logic\MDT\Data\MDTDungeon;
use App\Logic\MDT\Exception\ImportWarning;
use App\Logic\MDT\IO\MDTStringFormat;
use App\Models\AffixGroup\AffixGroup;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Enemy;
use App\Models\KillZone\KillZone;
use App\Models\Mapping\MappingVersion;
use App\Service\Cache\CacheServiceInterface;
use App\Service\Cache\Traits\RemembersToFile;
use App\Service\Coordinates\CoordinatesServiceInterface;
use App\Service\MDT\Export\ArrowExporter;
use App\Service\MDT\Export\KillZoneDescriptionExporter;
use App\Service\MDT\Export\KillZoneSpellsExporter;
use App\Service\MDT\Export\LineExporter;
use App\Service\MDT\Export\MapIconExporter;
use App\Service\MDT\Logging\MDTExportStringServiceLoggingInterface;
use Exception;
use Illuminate\Support\Collection;
use Psr\SimpleCache\InvalidArgumentException;

/**
 * This file handles any and all conversion from DungeonRoutes to MDT Export strings and vice versa.
 *
 * @author Wouter
 *
 * @since 09/11/2022
 */
class MDTExportStringService extends MDTBaseService implements MDTExportStringServiceInterface
{
    use RemembersToFile;

    /** @var DungeonRoute The route that's currently staged for conversion to an encoded string. */
    private DungeonRoute $dungeonRoute;

    public function __construct(
        private readonly CacheServiceInterface       $cacheService,
        private readonly CoordinatesServiceInterface $coordinatesService,
        private readonly MapIconExporter             $mapIconExporter,
        private readonly LineExporter                $lineExporter,
        private readonly ArrowExporter               $arrowExporter,
        private readonly KillZoneDescriptionExporter $killZoneDescriptionExporter,
        private readonly KillZoneSpellsExporter      $killZoneSpellsExporter,
        MDTExportStringServiceLoggingInterface       $log,
    ) {
        parent::__construct($log);
    }

    /**
     * @param  Collection<int, ImportWarning> $warnings
     * @return array<int, mixed>
     */
    private function extractObjects(Collection $warnings): array
    {
        $result = [];

        // Lua is 1 based, not 0 based
        $currentObjectIndex = 1;

        foreach ($this->mapIconExporter->export($this->dungeonRoute, $warnings) as $item) {
            $result[$currentObjectIndex++] = $item;
        }

        foreach ($this->lineExporter->export($this->dungeonRoute, $warnings) as $item) {
            $result[$currentObjectIndex++] = $item;
        }

        foreach ($this->arrowExporter->export($this->dungeonRoute, $warnings) as $item) {
            $result[$currentObjectIndex++] = $item;
        }

        foreach ($this->killZoneDescriptionExporter->export($this->dungeonRoute, $warnings) as $item) {
            $result[$currentObjectIndex++] = $item;
        }

        foreach ($this->killZoneSpellsExporter->export($this->dungeonRoute, $warnings) as $item) {
            $result[$currentObjectIndex++] = $item;
        }

        return $result;
    }

    /**
     * @param  Collection<int, ImportWarning> $warnings
     * @return array<int, mixed>
     *
     * @throws InvalidArgumentException
     */
    private function extractPulls(MappingVersion $mappingVersion, Collection $warnings): array
    {
        $result = [];

        // Get a list of MDT enemies as Keystone.guru enemies - we need this to know how to convert
        /** @var Collection<int, Enemy> $mdtEnemies */
        $mdtEnemies = new MDTDungeon($this->cacheService, $this->coordinatesService, $this->dungeonRoute->dungeon)
            ->getClonesAsEnemies($mappingVersion, $this->dungeonRoute->dungeon->floors);

        // Lua is 1 based, not 0 based
        $pullIndex = 1;
        /** @var Collection<int, KillZone> $killZones */
        $killZones = $this->dungeonRoute->loadMissing(['killZones.enemies.floor'])->killZones;
        foreach ($killZones as $killZone) {
            $pull = [];

            // Lua is 1 based, not 0 based
            $enemyIndex      = 1;
            $enemiesAdded    = 0;
            $killZoneEnemies = $killZone->getEnemies();
            foreach ($killZoneEnemies as $enemy) {
                // MDT does not handle prideful NPCs
                if ($enemy->npc->isPrideful()) {
                    continue;
                }

                // Find the MDT enemy - we need to know the mdt_npc_index
                $mdtNpcIndex = -1;
                foreach ($mdtEnemies as $mdtEnemyCandidate) {
                    if ($mdtEnemyCandidate->npc_id === $enemy->getMdtNpcId() && $mdtEnemyCandidate->mdt_id === $enemy->mdt_id) {
                        $mdtNpcIndex = $mdtEnemyCandidate->mdt_npc_index;
                        break;
                    }
                }

                // If we couldn't find the enemy in MDT..
                if ($mdtNpcIndex === -1) {
                    // Add a warning as long as it's not a boss - we don't particularly care since they have 0 count anyways
                    if (!$enemy->npc->isBoss()) {
                        $warnings->push(new ImportWarning(
                            sprintf(__('services.mdt.io.export_string.category.pull'), $pullIndex),
                            sprintf(__('services.mdt.io.export_string.unable_to_find_mdt_enemy_for_kg_enemy'), __($enemy->npc->name), $enemy->id, $enemy->getMdtNpcId()),
                            ['details' => __('services.mdt.io.export_string.unable_to_find_mdt_enemy_for_kg_enemy_details')],
                        ));
                    }

                    continue;
                }

                // Create an array if it didn't exist yet
                if (!isset($pull[$mdtNpcIndex])) {
                    $pull[$mdtNpcIndex] = [];
                }

                // For this enemy, kill this clone
                $pull[$mdtNpcIndex][] = $enemy->mdt_id;
                $enemiesAdded++;
            }

            // Do not add an empty pull if the killed enemy in our killzone was removed because it didn't exist in MDT, and that caused the pull to be empty
            if ($killZoneEnemies->count() !== 0 && $enemiesAdded === 0) {
                $warnings->push(new ImportWarning(
                    sprintf(__('services.mdt.io.export_string.category.pull'), $pullIndex),
                    __('services.mdt.io.export_string.unable_to_find_mdt_enemy_for_kg_caused_empty_pull'),
                ));

                continue;
            }

            $pull['color'] = str_starts_with($killZone->color, '#') ? substr($killZone->color, 1) : $killZone->color;

            $result[$pullIndex++] = $pull;
        }

        return $result;
    }

    /**
     * Builds MDT's raid target icon assignments ({mdtNpcIndex: {mdtCloneIndex: raidTargetIndex}}) -
     * the counterpart consumed by RaidMarkerImporter on import - from this route's raid markers.
     * npc_id/mdt_id on DungeonRouteEnemyRaidMarker are already the durable, mapping-version-current
     * identity (see #1453), so they're used directly instead of re-resolving through the enemy_id.
     *
     * @param  Collection<int, ImportWarning> $warnings
     * @return array<int, mixed>
     */
    private function extractEnemyAssignments(MappingVersion $mappingVersion, Collection $warnings): array
    {
        $result = [];

        $enemyRaidMarkers = $this->dungeonRoute->enemyRaidMarkers;
        if ($enemyRaidMarkers->isEmpty()) {
            return $result;
        }

        /** @var Collection<int, Enemy> $mdtEnemies */
        $mdtEnemies = new MDTDungeon($this->cacheService, $this->coordinatesService, $this->dungeonRoute->dungeon)
            ->getClonesAsEnemies($mappingVersion, $this->dungeonRoute->dungeon->floors);

        foreach ($enemyRaidMarkers as $enemyRaidMarker) {
            $mdtNpcIndex = -1;
            foreach ($mdtEnemies as $mdtEnemyCandidate) {
                if ($mdtEnemyCandidate->npc_id === $enemyRaidMarker->npc_id && $mdtEnemyCandidate->mdt_id === $enemyRaidMarker->mdt_id) {
                    $mdtNpcIndex = $mdtEnemyCandidate->mdt_npc_index;
                    break;
                }
            }

            if ($mdtNpcIndex === -1) {
                $warnings->push(new ImportWarning(
                    __('services.mdt.io.export_string.category.raid_markers'),
                    sprintf(
                        __('services.mdt.io.export_string.unable_to_find_mdt_enemy_for_kg_raid_marker'),
                        $enemyRaidMarker->raidMarker->name,
                        $enemyRaidMarker->npc_id,
                    ),
                    ['details' => __('services.mdt.io.export_string.unable_to_find_mdt_enemy_for_kg_enemy_details')],
                ));

                continue;
            }

            $result[$mdtNpcIndex] ??= [];
            $result[$mdtNpcIndex][$enemyRaidMarker->mdt_id] = $enemyRaidMarker->raid_marker_id;
        }

        return $result;
    }

    /**
     * Gets the MDT encoded string based on the currently set DungeonRoute.
     *
     * @param  Collection<int, ImportWarning> $warnings
     * @throws Exception
     */
    public function getEncodedString(Collection $warnings, bool $useCache = true): string
    {
        return $this->rememberLocal(
            // Spell names in the string are translated to the current locale
            sprintf(
                'mdt_export_string:%s_%s_%s',
                $this->dungeonRoute->id,
                $this->dungeonRoute->updated_at->timestamp,
                app()->getLocale(),
            ),
            config('keystoneguru.cache.mdt_export_strings.ttl'),
            function () use ($warnings) {
                //        $lua = $this->_getLua();

                $this->dungeonRoute->loadMissing([
                    'affixGroups',
                    'dungeon',
                ]);

                $affixes = $this->dungeonRoute->affixes()->with(['season'])->get();
                /** @var AffixGroup|null $firstAffixGroup */
                $firstAffixGroup = $affixes->first();

                $mdtObject = [
                    //
                    'objects' => $this->extractObjects($warnings),
                    // M+ level
                    'difficulty' => $this->dungeonRoute->level_min,
                    'week'       => $this->dungeonRoute->affixGroups->isEmpty() || $affixes->isEmpty() || $firstAffixGroup === null ? 1 :
                        Conversion::convertAffixGroupToWeek($firstAffixGroup),
                    'value' => [
                        'currentDungeonIdx' => $this->dungeonRoute->dungeon->mdt_id,
                        'selection'         => [],
                        'currentPull'       => 1,
                        'teeming'           => $this->dungeonRoute->teeming,
                        // Legacy - we don't do anything with it
                        'riftOffsets' => [

                        ],
                        'pulls'            => $this->extractPulls($this->dungeonRoute->mappingVersion, $warnings),
                        'enemyAssignments' => $this->extractEnemyAssignments($this->dungeonRoute->mappingVersion, $warnings),
                        'currentSublevel'  => 1,
                    ],
                    'text' => $this->dungeonRoute->title,
                    'mdi'  => [
                        'freeholdJoined' => false,
                        'freehold'       => 1,
                        'beguiling'      => 1,
                    ],
                    // Leave a consistent UID so multiple imports overwrite eachother - and a little watermark
                    'uid' => $this->dungeonRoute->public_key . 'xxKG',
                ];

                try {
                    return $this->encode($mdtObject, MDTStringFormat::MDT2);
                } catch (Exception $exception) {
                    // Encoding issue - adjust the title and try again
                    if (str_contains($exception->getMessage(), 'call to lua function [string &quot;line&quot;]')) {
                        $asciiTitle = preg_replace('/[[:^print:]]/', '', $this->dungeonRoute->title);

                        // If stripping ascii characters worked in changing the title somehow
                        if ($asciiTitle !== $this->dungeonRoute->title) {
                            $warnings->push(
                                new ImportWarning(
                                    __('services.mdt.io.export_string.category.title'),
                                    __('services.mdt.io.export_string.route_title_contains_non_ascii_char_bug'),
                                    ['details' => sprintf(__('services.mdt.io.export_string.route_title_contains_non_ascii_char_bug_details'), $this->dungeonRoute->title, $asciiTitle)],
                                ),
                            );
                            $this->dungeonRoute->title = $asciiTitle;

                            return $this->getEncodedString($warnings);
                        } else {
                            $fixedMapIconComment = false;

                            foreach ($this->dungeonRoute->mapicons as $mapIcon) {
                                $asciiComment = preg_replace('/[[:^print:]]/', '', $mapIcon->comment ?? '');
                                if ($asciiComment !== $mapIcon->comment) {
                                    $warnings->push(
                                        new ImportWarning(
                                            __('services.mdt.io.export_string.category.map_icon'),
                                            __('services.mdt.io.export_string.map_icon_contains_non_ascii_char_bug'),
                                            ['details' => sprintf(__('services.mdt.io.export_string.map_icon_contains_non_ascii_char_bug_details'), $asciiComment, $mapIcon->comment)],
                                        ),
                                    );
                                    $mapIcon->comment = $asciiComment;

                                    $fixedMapIconComment = true;
                                }
                            }

                            // If we fixed something, try again with encoding
                            if ($fixedMapIconComment) {
                                return $this->getEncodedString($warnings);
                            } else {
                                throw $exception;
                            }
                        }
                    } else {
                        throw $exception;
                    }
                }
            },
            $useCache,
        );
    }

    /**
     * Sets a dungeon route to be staged for encoding to an encoded string.
     *
     * @param        $dungeonRoute DungeonRoute
     * @return $this Returns self to allow for chaining.
     */
    public function setDungeonRoute(DungeonRoute $dungeonRoute): self
    {
        $this->dungeonRoute = $dungeonRoute;

        return $this;
    }
}
