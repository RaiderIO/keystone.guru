<?php

namespace App\Service\WowTools;

use App\Service\WowTools\Logging\WowToolsServiceLoggingInterface;

class WowToolsService implements WowToolsServiceInterface
{
    /** @var WowToolsServiceLoggingInterface */
    private WowToolsServiceLoggingInterface $log;

    /**
     * @param WowToolsServiceLoggingInterface $log
     */
    public function __construct(WowToolsServiceLoggingInterface $log)
    {
        $this->log = $log;
    }


    /**
     * @param int $npcId
     * @return int|null
     */
    public function getDisplayId(int $npcId): ?int
    {
        $this->log->getDisplayIdRequestStart($npcId);
        try {
            $result = null;

            $ch = curl_init();

            curl_setopt_array($ch, [
                CURLOPT_URL            => sprintf('https://wow.tools/db/creature_api.php?id=%d', $npcId),
                CURLOPT_RETURNTRANSFER => 1,
            ]);

            $requestResult = (array)json_decode(curl_exec($ch), true);

            if (isset($requestResult['error'])) {
                $this->log->getDisplayIdRequestError($requestResult['error']);
            } else {
                if (!isset($requestResult['CreatureDisplayInfoID[0]'])) {
                    $this->log->getDisplayIdRequestResultUnableFindCreateDisplayInfoID();
                } else {
                    $result = (int)$requestResult['CreatureDisplayInfoID[0]'];
                    $this->log->getDisplayIdRequestResult($result);
                }
            }
        } finally {
            $this->log->getDisplayIdRequestEnd();
        }

        return $result;
    }
}
