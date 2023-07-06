<?php

namespace App\Service\WowTools;

interface WowToolsServiceInterface
{
    public function getDisplayId(int $npcId): ?int;
}
