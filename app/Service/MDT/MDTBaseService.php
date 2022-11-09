<?php

namespace App\Service\MDT;

use Illuminate\Support\Facades\Artisan;
use Lua;

class MDTBaseService
{   /**
     * Gets a Lua instance and load all the required files in it.
     * @return Lua
     */
    protected function getLua() : Lua
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
     * @return array|null Null if the string could not be decoded
     */
    protected function decode(string $string): ?array
    {
        Artisan::call('mdt:decode', ['string' => $string]);

        return json_decode(trim(Artisan::output()), true);
    }
}
