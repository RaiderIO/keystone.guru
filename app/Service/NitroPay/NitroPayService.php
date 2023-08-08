<?php

namespace App\Service\NitroPay;

use App\Service\Traits\Curl;

class NitroPayService implements NitroPayServiceInterface
{
    use Curl;

    /**
     * @inheritDoc
     */
    public function getAdsTxt(int $userId): string
    {
        return $this->curlGet(sprintf('https://api.nitropay.com/v1/ads-%d.txt', $userId));
    }
}
