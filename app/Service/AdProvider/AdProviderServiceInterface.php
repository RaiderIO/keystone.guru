<?php

namespace App\Service\AdProvider;

interface AdProviderServiceInterface
{
    /**
     * @return string
     */
    public function getNitroPayAdsTxt(int $userId): string;

    /**
     * @return string
     */
    public function getPlaywireAdsTxt(int $param1, int $param2): string;
}
