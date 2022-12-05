<?php

namespace App\Service\WowTools;

use App\Service\WowTools\Logging\WowToolsServiceLoggingInterface;

class WowToolsService implements WowToolsServiceInterface
{
    /** @var WowToolsServiceLoggingInterface */
    private WowToolsServiceLoggingInterface $logging;

    /**
     * @param WowToolsServiceLoggingInterface $logging
     */
    public function __construct(WowToolsServiceLoggingInterface $logging)
    {
        $this->logging = $logging;
    }


    /**
     * @param int $npcId
     * @return int|null
     */
    public function getDisplayId(int $npcId): ?int
    {
        $this->logging->getDisplayIdRequestStart();
        try {
            $result = null;

            $ch = curl_init();

            curl_setopt_array($ch, [
                CURLOPT_URL            => sprintf('https://wow.tools/db/creature_api.php?id=%d', $npcId),
                CURLOPT_RETURNTRANSFER => 1,
            ]);

            $requestResult = (array)json_decode(curl_exec($ch), true);

            if (isset($requestResult['error'])) {
                $this->logging->getDisplayIdRequestError($requestResult['error']);
            } else {
                if (!isset($requestResult['CreatureDisplayInfoID[0]'])) {
                    $this->logging->getDisplayIdRequestResultUnableFindCreateDisplayInfoID();
                } else {
                    $result = (int)$requestResult['CreatureDisplayInfoID[0]'];
                    $this->logging->getDisplayIdRequestResult($result);
                }
            }
        } finally {
            $this->logging->getDisplayIdRequestEnd();
        }

        return $result;
    }
}
