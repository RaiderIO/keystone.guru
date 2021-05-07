<?php
/**
 * Created by PhpStorm.
 * User: Wouter
 * Date: 05/01/2019
 * Time: 20:49
 */

namespace App\Logic\MDT\IO;


use Illuminate\Support\Facades\Artisan;
use Lua;

/**
 * This file handles any and all conversion from DungeonRoutes to MDT Export strings and vice versa.
 *
 * @package App\Logic\MDT
 * @author Wouter
 * @since 05/01/2019
 */
class MDTBase
{

    /**
     * Gets a Lua instance and load all the required files in it.
     * @return Lua
     */
    protected function _getLua()
    {
        $lua = new Lua();

        // Load libraries (yeah can do this with ->library function as well)
        $lua->eval(file_get_contents(base_path('app/Logic/MDT/Lua/LibStub.lua')));
        $lua->eval(file_get_contents(base_path('app/Logic/MDT/Lua/LibCompress.lua')));
        $lua->eval(file_get_contents(base_path('app/Logic/MDT/Lua/LibDeflate.lua')));
        $lua->eval(file_get_contents(base_path('app/Logic/MDT/Lua/AceSerializer.lua')));
        $lua->eval(file_get_contents(base_path('app/Logic/MDT/Lua/MDTTransmission.lua')));

        return $lua;
    }

    /**
     * @param array $contents
     * @return string
     */
    protected function encode(array $contents): string
    {
        Artisan::call('mdt:encode', ['string' => json_encode($contents)]);

        return trim(Artisan::output());
    }

    /**
     * @param string $string
     * @return array
     */
    protected function decode(string $string): array
    {
        Artisan::call('mdt:decode', ['string' => $string]);

        return json_decode(trim(Artisan::output()), true);
    }
}