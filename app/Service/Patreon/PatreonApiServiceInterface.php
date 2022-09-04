<?php

namespace App\Service\Patreon;

use App\Logic\SimulationCraft\RaidEventsCollection;
use App\Models\SimulationCraft\SimulationCraftRaidEventsOptions;

interface PatreonApiServiceInterface
{
    public function getCampaignTiersAndBenefits(string $accessToken): ?array;

    public function getCampaignMembers(string $accessToken): ?array;

    public function getAccessTokenFromRefreshToken(string $refreshToken): array;

    public function getAccessTokenFromCode(string $code, string $redirectUrl): array;
}
