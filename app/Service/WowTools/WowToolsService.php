<?php

namespace App\Service\WowTools;

use App\Service\WowTools\Logging\WowToolsServiceLoggingInterface;

class WowToolsService implements WowToolsServiceInterface
{
    public function __construct(private readonly WowToolsServiceLoggingInterface $log)
    {
    }

    public function getDisplayId(int $npcId): ?int
    {
        return $this->log->getDisplayIdRequest($npcId, function () use ($npcId): ?int {
            $result = null;

            $ch = curl_init();

            curl_setopt_array($ch, [
                CURLOPT_URL            => sprintf('https://old.wow.tools/db/creature_api.php?id=%d', $npcId),
                CURLOPT_RETURNTRANSFER => true,
            ]);

            $requestResult = (array)json_decode(curl_exec($ch), true);

            if (empty($requestResult)) {
                $this->log->getDisplayIdInvalidResponse();
            } elseif (isset($requestResult['error'])) {
                $this->log->getDisplayIdRequestError($requestResult['error']);
            } else {
                if (!isset($requestResult['CreatureDisplayInfoID[0]'])) {
                    $this->log->getDisplayIdRequestResultUnableFindCreateDisplayInfoID();
                } else {
                    $result = (int)$requestResult['CreatureDisplayInfoID[0]'];
                    $this->log->getDisplayIdRequestResult($result);
                }
            }

            return $result;
        });
    }
}
