<?php

namespace App\Service\AffixGroup;

use App\Service\AffixGroup\Exceptions\InvalidResponseException;
use App\Service\Traits\Curl;

class ArchonApiService implements ArchonApiServiceInterface
{
    use Curl;

    /**
     * {@inheritDoc}
     */
    public function getDungeonEaseTierListOverall(): array
    {
        $responseStr = $this->curlGet('https://www.archon.gg/wow/tier-list/dps-rankings/mythic-plus/20/all-dungeons/this-week/data.json');

        $response = json_decode($responseStr, true);

        if (! is_array($response)) {
            throw new InvalidResponseException($responseStr);
        }

        // Temp fix for strange characters being put in front of the affix list
        $response['encounterTierList']['label'] = trim((string) $response['encounterTierList']['label'], '‍');

        return $response;
    }
}
