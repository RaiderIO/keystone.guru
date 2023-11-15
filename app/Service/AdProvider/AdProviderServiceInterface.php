<?php

namespace App\Service\AdProvider;

interface AdProviderServiceInterface
{
    /**
     * @param int $userId
     * @return string
     */
    public function getNitroPayAdsTxt(int $userId): string;

    /**
     * @param int $param1
     * @param int $param2
     * @return string
     */
    public function getPlaywireAdsTxt(int $param1, int $param2): string;
}
