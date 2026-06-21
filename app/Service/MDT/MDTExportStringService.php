<?php

namespace App\Service\MDT;

use App\Logic\MDT\Conversion;
use App\Logic\MDT\Data\MDTDungeon;
use App\Logic\MDT\Exception\ImportWarning;
use App\Models\Arrow;
use App\Models\Brushline;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Enemy;
use App\Models\KillZone\KillZone;
use App\Models\MapIcon;
use App\Models\Mapping\MappingVersion;
use App\Models\Path;
use App\Service\Cache\CacheServiceInterface;
use App\Service\Cache\Traits\RemembersToFile;
use App\Service\Coordinates\CoordinatesServiceInterface;
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

    /** @var int How far away do we create notes in MDT */
    private const int KILL_ZONE_DESCRIPTION_DISTANCE = 3;

    /** @var DungeonRoute The route that's currently staged for conversion to an encoded string. */
    private DungeonRoute $dungeonRoute;

    public function __construct(
        private readonly CacheServiceInterface       $cacheService,
        private readonly CoordinatesServiceInterface $coordinatesService,
    ) {
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

        foreach ($this->extractMapIconObjects() as $item) {
            $result[$currentObjectIndex++] = $item;
        }

        foreach ($this->extractLineObjects() as $item) {
            $result[$currentObjectIndex++] = $item;
        }

        foreach ($this->extractArrowObjects() as $item) {
            $result[$currentObjectIndex++] = $item;
        }

        foreach ($this->extractKillZoneDescriptionObjects() as $item) {
            $result[$currentObjectIndex++] = $item;
        }

        return $result;
    }

    /** @return array<int, mixed> */
    private function extractMapIconObjects(): array
    {
        $objects = [];

        foreach ($this->dungeonRoute->mapicons()->with(['floor'])->get() as $mapIcon) {
            /** @var MapIcon $mapIcon */
            $latLng = $mapIcon->getLatLng();
            if ($this->dungeonRoute->mappingVersion->facade_enabled) {
                $latLng = $this->coordinatesService->convertMapLocationToFacadeMapLocation(
                    $this->dungeonRoute->mappingVersion,
                    $latLng,
                );
            }

            $mdtCoordinates = Conversion::convertLatLngToMDTCoordinateString($latLng);

            $objects[] = [
                'n' => true,
                'd' => [
                    1 => $mdtCoordinates['x'],
                    2 => $mdtCoordinates['y'],
                    3 => $latLng->getFloor()->mdt_sub_level ?? $latLng->getFloor()->index,
                    4 => true,
                    5 => $this->convertHtmlToMdtComment($mapIcon->comment ?? __($mapIcon->mapIconType?->name) ?? ''), // @phpstan-ignore nullsafe.neverNull, nullCoalesce.expr
                ],
            ];
        }

        return $objects;
    }

    /** @return array<int, mixed> */
    private function extractLineObjects(): array
    {
        $objects = [];

        /** @var \Illuminate\Support\Collection<int, Path|Brushline> $lines */
        $lines = $this->dungeonRoute->brushlines()->with(['floor'])->get()->toBase()->merge(
            $this->dungeonRoute->paths()->with(['floor'])->get()->toBase(),
        );

        foreach ($lines as $line) {
            /** @var Path|Brushline $line */
            $mdtLine = [
                'd' => [
                    1 => $line->polyline->weight,
                    2 => 1,
                    3 => $line->floor->mdt_sub_level ?? $line->floor->index,
                    4 => true,
                    5 => str_starts_with($line->polyline->color, '#') ? substr($line->polyline->color, 1) : $line->polyline->color,
                    6 => -8,
                    7 => true,
                ],
                'l' => [],
            ];

            if ($line instanceof Brushline) {
                $mdtLine['d'][7] = true;
            }

            $vertexIndex            = 1;
            $verticesLatLngs        = $line->polyline->getDecodedLatLngs($line->floor);
            $previousMdtCoordinates = null;

            foreach ($verticesLatLngs as $vertexLatLng) {
                if ($this->dungeonRoute->mappingVersion->facade_enabled) {
                    $vertexLatLng = $this->coordinatesService->convertMapLocationToFacadeMapLocation(
                        $this->dungeonRoute->mappingVersion,
                        $vertexLatLng,
                    );

                    // The floor of the line should be updated too
                    $mdtLine['d'][3] = $vertexLatLng->getFloor()->mdt_sub_level ?? $vertexLatLng->getFloor()->index;
                }

                $mdtCoordinates = Conversion::convertLatLngToMDTCoordinateString($vertexLatLng);

                if ($previousMdtCoordinates !== null) {
                    // We must do A -> B, B -> C, C -> D. I don't know why he wants the previous coordinates too, but alas that's how it works
                    $mdtLine['l'][$vertexIndex++] = $previousMdtCoordinates['x'];
                    $mdtLine['l'][$vertexIndex++] = $previousMdtCoordinates['y'];
                    $mdtLine['l'][$vertexIndex++] = $mdtCoordinates['x'];
                    $mdtLine['l'][$vertexIndex++] = $mdtCoordinates['y'];
                }

                $previousMdtCoordinates = $mdtCoordinates;
            }

            $objects[] = $mdtLine;
        }

        return $objects;
    }

    /**
     * Export arrows as MDT triangle objects.
     *
     * @return array<int, mixed>
     */
    private function extractArrowObjects(): array
    {
        $objects = [];

        foreach ($this->dungeonRoute->arrows()->with(['floor'])->get() as $arrow) {
            /** @var Arrow $arrow */
            if ($arrow->polyline === null) {
                continue;
            }

            $verticesLatLngs = $arrow->polyline->getDecodedLatLngs($arrow->floor);

            $mdtLine = [
                'd' => [
                    1 => $arrow->polyline->weight,
                    2 => 1,
                    3 => $arrow->floor->mdt_sub_level ?? $arrow->floor->index,
                    4 => true,
                    5 => str_starts_with($arrow->polyline->color, '#') ? substr($arrow->polyline->color, 1) : $arrow->polyline->color,
                    6 => -8,
                    7 => true,
                ],
                'l' => [],
                't' => [],
            ];

            $vertexIndex            = 1;
            $firstMdtCoordinates    = null;
            $lastMdtCoordinates     = null;
            $previousMdtCoordinates = null;

            foreach ($verticesLatLngs as $vertexLatLng) {
                if ($this->dungeonRoute->mappingVersion->facade_enabled) {
                    $vertexLatLng = $this->coordinatesService->convertMapLocationToFacadeMapLocation(
                        $this->dungeonRoute->mappingVersion,
                        $vertexLatLng,
                    );

                    $mdtLine['d'][3] = $vertexLatLng->getFloor()->mdt_sub_level ?? $vertexLatLng->getFloor()->index;
                }

                $mdtCoordinates = Conversion::convertLatLngToMDTCoordinateString($vertexLatLng);

                $firstMdtCoordinates ??= $mdtCoordinates;
                $lastMdtCoordinates = $mdtCoordinates;

                if ($previousMdtCoordinates !== null) {
                    $mdtLine['l'][$vertexIndex++] = $previousMdtCoordinates['x'];
                    $mdtLine['l'][$vertexIndex++] = $previousMdtCoordinates['y'];
                    $mdtLine['l'][$vertexIndex++] = $mdtCoordinates['x'];
                    $mdtLine['l'][$vertexIndex++] = $mdtCoordinates['y'];
                }

                $previousMdtCoordinates = $mdtCoordinates;
            }

            // Compute arrow direction from shaft: atan2(dy, dx) of the last segment
            if ($firstMdtCoordinates !== null && $lastMdtCoordinates !== null) {
                $dx              = (float)$lastMdtCoordinates['x'] - (float)$firstMdtCoordinates['x'];
                $dy              = (float)$lastMdtCoordinates['y'] - (float)$firstMdtCoordinates['y'];
                $mdtLine['t'][1] = atan2($dy, $dx);
            }

            $objects[] = $mdtLine;
        }

        return $objects;
    }

    /**
     * For each kill zone, extract its description as an MDT note object.
     *
     * @return array<int, mixed>
     */
    private function extractKillZoneDescriptionObjects(): array
    {
        $objects = [];

        foreach ($this->dungeonRoute->killZones as $killZone) {
            if (!isset($killZone->description)) {
                continue;
            }

            $floor  = $killZone->getDominantFloor();
            $latLng = $killZone->getEnemiesBoundingBoxNorthEdgeMiddleCoordinate(self::KILL_ZONE_DESCRIPTION_DISTANCE);

            // Maybe the pull had no enemies
            if ($latLng === null) {
                continue;
            }

            if ($this->dungeonRoute->mappingVersion->facade_enabled) {
                $latLng = $this->coordinatesService->convertMapLocationToFacadeMapLocation(
                    $this->dungeonRoute->mappingVersion,
                    $latLng,
                );
            }

            $mdtCoordinates = Conversion::convertLatLngToMDTCoordinateString($latLng);

            $objects[] = [
                'n' => true,
                'd' => [
                    1 => $mdtCoordinates['x'],
                    2 => $mdtCoordinates['y'],
                    3 => $floor->mdt_sub_level ?? $floor->index,
                    4 => true,
                    5 => $this->convertHtmlToMdtComment($killZone->description),
                    // MDT does not support HTML tags - get rid of them.
                ],
            ];
        }

        return $objects;
    }

    /**
     * Convert HTML to a format MDT understands:
     * - Replace <a href="...">...</a> with "(href)"
     * - Strip all remaining HTML tags.
     */
    private function convertHtmlToMdtComment(?string $html): string
    {
        $html ??= '';

        if ($html === '') {
            return '';
        }

        // Replace anchors with "(href)"
        $html = preg_replace_callback(
            '/<a\b[^>]*?href=(?:"([^"]+)"|\'([^\']+)\')[^>]*>.*?<\/a>/i',
            static function (array $matches): string {
                $href = $matches[1] !== '' ? $matches[1] : $matches[2];

                return sprintf('(%s)', $href);
            },
            $html,
        );

        // Strip any remaining HTML tags
        return trim(strip_tags((string)$html));
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
        $killZones = $this->dungeonRoute->killZones()->get();
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
     * Gets the MDT encoded string based on the currently set DungeonRoute.
     *
     * @param  Collection<int, ImportWarning> $warnings
     * @throws Exception
     */
    public function getEncodedString(Collection $warnings, bool $useCache = true): string
    {
        return $this->rememberLocal(
            sprintf('mdt_export_string:%s_%s', $this->dungeonRoute->id, $this->dungeonRoute->updated_at->timestamp),
            config('keystoneguru.cache.mdt_export_strings.ttl'),

            // #2945 I just had a weird issue where copying an MDT string from one route, would actually generate a string
            // based on another route. The route title was extracted correctly, but the pulls/map icons were from
            // another route entirely. Clearing the caches solved this issue. I'm disabling the model cache for this operation
            // as a result.

            fn() => app('model-cache')->runDisabled(function () use ($warnings) {
                //        $lua = $this->_getLua();

                $affixes = $this->dungeonRoute->affixes()->with(['season'])->get();
                /** @var \App\Models\AffixGroup\AffixGroup|null $firstAffixGroup */
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
                        'pulls'           => $this->extractPulls($this->dungeonRoute->mappingVersion, $warnings),
                        'currentSublevel' => 1,
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
                    return $this->encode($mdtObject);
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
            }),
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
        $this->dungeonRoute = $dungeonRoute->load([
            'affixGroups',
            'dungeon',
        ]);

        return $this;
    }
}
