<?php

namespace App\Service\AdProvider;

interface AdProviderServiceInterface
{
    public function getPlaywireAdsTxt(int $param1, int $param2): string;
}
