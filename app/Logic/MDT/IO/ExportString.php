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
use App\Models\DungeonRoute;
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
    private $_encodedString;

    /** @var DungeonRoute The route that's currently staged for conversion to an encoded string. */
    private $_dungeonRoute;

    /** @var SeasonService Used for grabbing info about the current M+ season. */
    private $_seasonService;


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

        return $result;
    }

    /**
     * @param Collection $warnings
     * @return array
     */
    private function _extractPulls(Collection $warnings): array
    {
        $result = [];

        // Get a list of MDT enemies as Keystone.guru enemies - we need this to know how to convert
        $mdtEnemies = (new MDTDungeon($this->_dungeonRoute->dungeon->name))
            ->getClonesAsEnemies($this->_dungeonRoute->dungeon->floors);

        // LUA is 1 index based, not 0
        $pullIndex = 1;
        foreach ($this->_dungeonRoute->killzones as $killZone) {
            $pull = [];

            foreach ($killZone->enemies as $enemy) {
                // Find the MDT enemy - we need to know the mdt_npc_index
                $mdtNpcIndex = -1;
                foreach ($mdtEnemies as $mdtEnemyCandidate) {
                    if ($mdtEnemyCandidate->npc_id === $enemy->npc_id && $mdtEnemyCandidate->mdt_id === $enemy->mdt_id) {
                        $mdtNpcIndex = $mdtEnemyCandidate->mdt_npc_index;
                        break;
                    }
                }

                // Create an array if it didn't exist yet
                if (!isset($pull[$mdtNpcIndex])) {
                    $pull[$mdtNpcIndex] = [];
                }

                // For this enemy, kill this clone
                $pull[$mdtNpcIndex][] = $enemy->mdt_id;
            }

            $pull['color'] = strpos($killZone->color, '#') === 0 ? substr($killZone->color, 1) : $killZone->color;

            $result[$pullIndex] = $pull;
            $pullIndex++;
        }

        return $result;
    }


    /**
     * Gets the MDT encoded string based on the currently set DungeonRoute.
     * @param Collection $warnings
     * @return string
     */
    public function getEncodedString(Collection $warnings)
    {
        $lua = $this->_getLua();

        $mdtObject = [
            //
            'objects'    => $this->_extractObjects($warnings),
            // M+ level
            'difficulty' => 10,
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
                'currentSublevel'   => 1
            ],
            'text'       => $this->_dungeonRoute->title,
            'mdi'        => [
                'freeholdJoined' => false,
                'freehold'       => 1,
                'beguiling'      => 1
            ],
            // Leave a consistent UID so multiple imports overwrite eachother - and a little watermark
            'uid'        => $this->_dungeonRoute->public_key . 'xxKG',
        ];


        return $lua->call("TableToString", [$mdtObject, true]);
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