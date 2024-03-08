<?php

namespace App\Service\AdProvider;

use App\Service\Traits\Curl;

class AdProviderService implements AdProviderServiceInterface
{
    use Curl;

    /**
     * {@inheritDoc}
     */
    public function getNitroPayAdsTxt(int $userId): string
    {
        return $this->curlGet(sprintf('https://api.nitropay.com/v1/ads-%d.txt', $userId));
    }

    /**
     * {@inheritDoc}
     */
    public function getPlaywireAdsTxt(int $param1, int $param2): string
    {
        return $this->curlGet(sprintf('https://config.playwire.com/dyn_ads/%d/%d/ads.txt', $param1, $param2));
    }
}
