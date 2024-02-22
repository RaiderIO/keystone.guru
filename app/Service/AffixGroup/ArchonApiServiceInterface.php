<?php


namespace App\Service\AffixGroup;

use App\Service\AffixGroup\Exceptions\InvalidResponseException;

interface ArchonApiServiceInterface
{
    /**
     * @return array
     * @throws InvalidResponseException
     */
    public function getDungeonEaseTierListOverall(): array;
}
