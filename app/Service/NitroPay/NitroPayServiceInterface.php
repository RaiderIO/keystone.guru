<?php

namespace App\Service\NitroPay;

interface NitroPayServiceInterface
{
    /**
     * @param int $userId
     * @return string
     */
    public function getAdsTxt(int $userId): string;
}
