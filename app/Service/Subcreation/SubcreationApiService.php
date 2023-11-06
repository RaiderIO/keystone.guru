<?php


namespace App\Service\Subcreation;

use App\Service\Subcreation\Exceptions\InvalidResponseException;
use App\Service\Traits\Curl;

class SubcreationApiService implements SubcreationApiServiceInterface
{
    use Curl;

    /**
     * @return array
     * @throws InvalidResponseException
     */
    public function getDungeonEaseTierListOverall(): array
    {
        $responseStr = $this->curlGet('https://subcreation.net/api/v0/dungeon_ease_tier_list_overall');

        $response = json_decode($responseStr, true);

        if (!is_array($response)) {
            throw new InvalidResponseException($responseStr);
        }

        return $response;
    }
}
