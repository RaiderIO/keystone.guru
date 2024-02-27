<?php

namespace App\Service\AffixGroup;

use App\Service\AffixGroup\Exceptions\InvalidResponseException;

interface ArchonApiServiceInterface
{
    /**
     * @throws InvalidResponseException
     */
    public function getDungeonEaseTierListOverall(): array;
}
