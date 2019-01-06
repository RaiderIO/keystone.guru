<?php
/**
 * Created by PhpStorm.
 * User: Wouter
 * Date: 05/01/2019
 * Time: 20:49
 */

namespace App\Logic\MDT\IO;


use App\Models\DungeonRoute;

/**
 * This file handles any and all conversion from DungeonRoutes to MDT Export strings and vice versa.
 * @package App\Logic\MDT
 * @author Wouter
 * @since 05/01/2019
 */
class ImportString
{
    /**
     * @var string The MDT encoded string that's currently staged for conversion to a DungeonRoute.
     */
    private $_encodedString;

    /**
     * @var DungeonRoute The route that's currently staged for conversion to an encoded string.
     */
    private $_dungeonRoute;


    function __construct()
    {

    }

    /**
     * Gets a Lua instance and load all the required files in it.
     * @return \Lua
     */
    private function _getLua()
    {
        $lua = new \Lua();

        // Load libraries (yeah can do this with ->library function as well)
        $lua->eval(file_get_contents(base_path('app/Logic/MDT/Lua/LibStub.lua')));
        $lua->eval(file_get_contents(base_path('app/Logic/MDT/Lua/LibCompress.lua')));
        $lua->eval(file_get_contents(base_path('app/Logic/MDT/Lua/AceSerializer.lua')));
        $lua->eval(file_get_contents(base_path('app/Logic/MDT/Lua/MDTTransmission.lua')));

        return $lua;
    }

    /**
     * Gets the MDT encoded string based on the currently set DungeonRoute.
     * @return string
     */
    public function getEncodedString()
    {
        // @TODO This needs a dungeon route to "table" conversion first
        $lua = $this->_getLua();
        $encoded = $lua->call("TableToString", [$this->_dungeonRoute, true]);
        return $encoded;
    }

    /**
     * Gets the dungeon route based on the currently encoded string.
     * @return DungeonRoute
     */
    public function getDungeonRoute()
    {
        // @TODO This needs a "table" to dungeon route conversion first
        $lua = $this->_getLua();
        $decoded = $lua->call("StringToTable", [$this->_encodedString, true]);
        return $decoded;
    }

    /**
     * Sets the encoded string to be staged for translation to a DungeonRoute.
     *
     * @param $encodedString string The MDT encoded string.
     * @return $this
     */
    public function setEncodedString($encodedString)
    {
        $this->_encodedString = $encodedString;

        return $this;
    }

    /**
     * Sets a dungeon route to be staged for encoding to an encoded string.
     *
     * @param $dungeonRoute DungeonRoute
     * @return $this Returns self to allow for chaining.
     */
    public function setDungeonRoute($dungeonRoute)
    {
        $this->_dungeonRoute = $dungeonRoute;

        return $this;
    }
}