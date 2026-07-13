<?php

namespace App\Service\AffixGroup;

use App\Service\AffixGroup\Exceptions\InvalidResponseException;

interface ArchonApiServiceInterface
{
    /**
     * @throws InvalidResponseException
     * @return array<string, mixed>
     */
    public function getDungeonEaseTierListOverall(): array;
}
