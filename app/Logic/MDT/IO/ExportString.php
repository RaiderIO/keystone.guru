<?php
/**
 * Created by PhpStorm.
 * User: Wouter
 * Date: 05/01/2019
 * Time: 20:49
 */

namespace App\Logic\MDT\IO;


use App\Logic\MDT\Conversion;
use App\Logic\MDT\Data\MDTDungeon;
use App\Logic\MDT\Exception\ImportWarning;
use App\Models\Brushline;
use App\Models\DungeonRoute;
use App\Models\KillZone;
use App\Models\NpcClassification;
use App\Models\Path;
use App\Service\Season\SeasonService;
use Illuminate\Support\Collection;

/**
 * This file handles any and all conversion from DungeonRoutes to MDT Export strings and vice versa.
 * @package App\Logic\MDT
 * @author Wouter
 * @since 05/01/2019
 */
class ExportString extends MDTBase
{
    /** @var $_encodedString string The MDT encoded string that's currently staged for conversion to a DungeonRoute. */
    private string $_encodedString;

    /** @var DungeonRoute The route that's currently staged for conversion to an encoded string. */
    private DungeonRoute $_dungeonRoute;

    /** @var SeasonService Used for grabbing info about the current M+ season. */
    private SeasonService $_seasonService;


    function __construct(SeasonService $seasonService)
    {
        $this->_seasonService = $seasonService;
    }

    /**
     * @param Collection $warnings
     * @return array
     */
    private function _extractObjects(Collection $warnings): array
    {
        $result = [];

        // Lua is 1 based, not 0 based
        $currentObjectIndex = 1;
        foreach ($this->_dungeonRoute->mapicons as $mapIcon) {
            $mdtCoordinates = Conversion::convertLatLngToMDTCoordinate(['lat' => $mapIcon->lat, 'lng' => $mapIcon->lng]);

            $result[$currentObjectIndex++] = [
                'n' => true,
                'd' => [
                    1 => $mdtCoordinates['x'],
                    2 => $mdtCoordinates['y'],
                    3 => $mapIcon->floor->index,
                    4 => true,
                    5 => $mapIcon->comment,
                ],
            ];
        }

        $lines = $this->_dungeonRoute->brushlines->merge($this->_dungeonRoute->paths);

        foreach ($lines as $line) {
            /** @var Path|Brushline $line */

            $mdtLine = [
                'd' => [
                    1 => $line->polyline->weight,
                    2 => 1,
                    3 => $line->floor->index,
                    4 => true,
                    5 => strpos($line->polyline->color, '#') === 0 ? substr($line->polyline->color, 1) : $line->polyline->color,
                    6 => -8,
                    7 => true,
                ],
                'l' => [],
            ];

            if ($line instanceof Brushline) {
                $mdtLine['d'][7] = true;
            }

            $vertexIndex            = 1;
            $vertices               = json_decode($line->polyline->vertices_json, true);
            $previousMdtCoordinates = null;
            foreach ($vertices as $latLng) {
                $mdtCoordinates = Conversion::convertLatLngToMDTCoordinate($latLng);

                if ($previousMdtCoordinates !== null) {
                    // We must do A -> B, B -> C, C -> D. I don't know why he wants the previous coordinates too, but alas that's how it works
                    $mdtLine['l'][$vertexIndex++] = $previousMdtCoordinates['x'];
                    $mdtLine['l'][$vertexIndex++] = $previousMdtCoordinates['y'];
                    $mdtLine['l'][$vertexIndex++] = $mdtCoordinates['x'];
                    $mdtLine['l'][$vertexIndex++] = $mdtCoordinates['y'];
                }

                $previousMdtCoordinates = $mdtCoordinates;
            }

            $result[$currentObjectIndex++] = $mdtLine;
        }

        return $result;
    }

    /**
     * @param Collection $warnings
     * @return array
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    private function _extractPulls(Collection $warnings): array
    {
        $result = [];

        // Get a list of MDT enemies as Keystone.guru enemies - we need this to know how to convert
        $mdtEnemies = (new MDTDungeon($this->_dungeonRoute->dungeon->key))
            ->getClonesAsEnemies($this->_dungeonRoute->dungeon->floors);

        // Lua is 1 based, not 0 based
        $pullIndex = 1;
        /** @var Collection|KillZone[] $killZones */
        $killZones = $this->_dungeonRoute->killzones()->with(['enemies'])->get();
        foreach ($killZones as $killZone) {
            $pull = [];

            // Lua is 1 based, not 0 based
            $enemyIndex   = 1;
            $enemiesAdded = 0;
            foreach ($killZone->enemies as $enemy) {
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
                    if (!in_array($enemy->npc->classification->shortname, [NpcClassification::NPC_CLASSIFICATION_BOSS, NpcClassification::NPC_CLASSIFICATION_FINAL_BOSS])) {
                        $warnings->push(new ImportWarning(sprintf(__('logic.mdt.io.export_string.category.pull'), $pullIndex),
                            sprintf(__('logic.mdt.io.export_string.unable_to_find_mdt_enemy_for_kg_enemy'), $enemy->npc->name, $enemy->id, $enemy->getMdtNpcId()),
                            ['details' => __('logic.mdt.io.export_string.unable_to_find_mdt_enemy_for_kg_enemy_details')]
                        ));
                    }

                    continue;
                }

                // Create an array if it didn't exist yet
                if (!isset($pull[$mdtNpcIndex])) {
                    $pull[$mdtNpcIndex] = [];
                }

                // For this enemy, kill this clone
                $pull[$mdtNpcIndex][$enemyIndex++] = $enemy->mdt_id;
                $enemiesAdded++;
            }

            // Do not add an empty pull if the killed enemy in our killzone was removed because it didn't exist in MDT, and that caused the pull to be empty
            if ($enemiesAdded === 0) {
                $warnings->push(new ImportWarning(sprintf(__('logic.mdt.io.export_string.category.pull'), $pullIndex),
                    __('logic.mdt.io.export_string.unable_to_find_mdt_enemy_for_kg_caused_empty_pull'),
                ));

                continue;
            }

            $pull['color'] = strpos($killZone->color, '#') === 0 ? substr($killZone->color, 1) : $killZone->color;

            $result[$pullIndex++] = $pull;
        }

        return $result;
    }


    /**
     * Gets the MDT encoded string based on the currently set DungeonRoute.
     * @param Collection $warnings
     * @return string
     * @throws \Exception
     */
    public function getEncodedString(Collection $warnings): string
    {
//        $lua = $this->_getLua();

        $mdtObject = [
            //
            'objects'    => $this->_extractObjects($warnings),
            // M+ level
            'difficulty' => $this->_dungeonRoute->level_min,
            'week'       => $this->_dungeonRoute->affixgroups->isEmpty() ? 1 :
                Conversion::convertAffixGroupToWeek($this->_seasonService, $this->_dungeonRoute->affixes->first()),
            'value'      => [
                'currentDungeonIdx' => $this->_dungeonRoute->dungeon->mdt_id,
                'selection'         => [],
                'currentPull'       => 1,
                'teeming'           => $this->_dungeonRoute->teeming,
                // Legacy - we don't do anything with it
                'riftOffsets'       => [

                ],
                'pulls'             => $this->_extractPulls($warnings),
                'currentSublevel'   => 1,
            ],
            'text'       => $this->_dungeonRoute->title,
            'mdi'        => [
                'freeholdJoined' => false,
                'freehold'       => 1,
                'beguiling'      => 1,
            ],
            // Leave a consistent UID so multiple imports overwrite eachother - and a little watermark
            'uid'        => $this->_dungeonRoute->public_key . 'xxKG',
        ];

        try {
            return $this->encode($mdtObject);
        } catch (\Exception $exception) {
            // Encoding issue - adjust the title and try again
            if (str_contains($exception->getMessage(), "call to lua function [string &quot;line&quot;]")) {
                $asciiTitle = preg_replace('/[[:^print:]]/', '', $this->_dungeonRoute->title);

                // If stripping ascii characters worked in changing the title somehow
                if ($asciiTitle !== $this->_dungeonRoute->title) {
                    $warnings->push(
                        new ImportWarning(__('logic.mdt.io.export_string.category.title'),
                            __('logic.mdt.io.export_string.route_title_contains_non_ascii_char_bug'),
                            ['details' => sprintf(__('logic.mdt.io.export_string.route_title_contains_non_ascii_char_bug_details'), $this->_dungeonRoute->title, $asciiTitle)]
                        )
                    );
                    $this->_dungeonRoute->title = $asciiTitle;

                    return $this->getEncodedString($warnings);
                } else {
                    $fixedMapIconComment = false;

                    foreach ($this->_dungeonRoute->mapicons as $mapicon) {
                        $asciiComment = preg_replace('/[[:^print:]]/', '', $mapicon->comment);
                        if ($asciiComment !== $mapicon->comment) {
                            $warnings->push(
                                new ImportWarning(__('logic.mdt.io.export_string.category.map_icon'),
                                    __('logic.mdt.io.export_string.map_icon_contains_non_ascii_char_bug'),
                                    ['details' => sprintf(__('logic.mdt.io.export_string.map_icon_contains_non_ascii_char_bug_details'), $asciiComment, $mapicon->comment)]
                                )
                            );
                            $mapicon->comment = $asciiComment;

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
    }

    /**
     * Sets a dungeon route to be staged for encoding to an encoded string.
     *
     * @param $dungeonRoute DungeonRoute
     * @return $this Returns self to allow for chaining.
     */
    public function setDungeonRoute(DungeonRoute $dungeonRoute): ExportString
    {
        $this->_dungeonRoute = $dungeonRoute->load(['affixgroups', 'dungeon']);

        return $this;
    }
}
