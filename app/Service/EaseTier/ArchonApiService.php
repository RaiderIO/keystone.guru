<?php


namespace App\Service\EaseTier;

use App\Service\EaseTier\Exceptions\InvalidResponseException;
use App\Service\Traits\Curl;

class ArchonApiService implements ArchonApiServiceInterface
{
    use Curl;

    /**
     * @return array
     * @throws InvalidResponseException
     */
    public function getDungeonEaseTierListOverall(): array
    {
        $responseStr = $this->curlGet('https://www.archon.gg/wow/tier-list/dps-rankings/mythic-plus/20/all-dungeons/this-week/data.json');

        $response = json_decode($responseStr, true);

        if (!is_array($response)) {
            throw new InvalidResponseException($responseStr);
        }

        return $response;
    }
}
