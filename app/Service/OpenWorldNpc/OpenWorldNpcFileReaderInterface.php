<?php

namespace App\Service\OpenWorldNpc;

use App\Service\OpenWorldNpc\Dtos\OpenWorldNpcFile;
use App\Service\OpenWorldNpc\Exceptions\InvalidOpenWorldNpcFileException;

/**
 * Turns the contents of Raider.IO's deadly-NPC list into entries. The only class that knows the file's format.
 */
interface OpenWorldNpcFileReaderInterface
{
    /**
     * @throws InvalidOpenWorldNpcFileException When the file as a whole cannot be read, or none of its entries can
     */
    public function read(string $contents): OpenWorldNpcFile;
}
