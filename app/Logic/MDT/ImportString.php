<?php
/**
 * Created by PhpStorm.
 * User: Wouter
 * Date: 05/01/2019
 * Time: 20:49
 */

namespace App\Logic\MDT;


use App\Huffman;
use App\Models\DungeonRoute;

/**
 * Class ImportString. This file was created as a sort of copy of https://github.com/nnogga/MethodDungeonTools/blob/master/Transmission.lua
 * All rights belong to their respective owners, I did write this but I did not make this up.  I merely translated the LUA
 * to PHP to allow for importing of the exported strings.
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
        // $lua->assign("php_var", array(1=>1, 2, 3)); //lua table index begin with 1
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