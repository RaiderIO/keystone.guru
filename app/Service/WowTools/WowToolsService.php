<?php

namespace App\Service\WowTools;

class WowToolsService implements WowToolsServiceInterface
{
    /**
     * @param int $npcId
     * @return int|null
     */
    public function getDisplayId(int $npcId): ?int
    {
        $result = null;

        $ch = curl_init();

        curl_setopt_array($ch, [
            CURLOPT_URL            => sprintf('https://wow.tools/db/creature_api.php?id=%d', $npcId),
            CURLOPT_RETURNTRANSFER => 1,
        ]);

        $requestResult = (array)json_decode(curl_exec($ch), true);

        if (isset($requestResult['error'])) {
            dump($requestResult);
        } else {
            $result = (int)$requestResult['CreatureDisplayInfoID[0]'];
        }

        return $result;
    }
}
