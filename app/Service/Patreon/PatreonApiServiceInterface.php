<?php

namespace App\Service\Patreon;

use App\Service\Patreon\Dtos\PatreonPagedResponse;

interface PatreonApiServiceInterface
{
    /**
     * @return array<string, mixed>|null
     */
    public function getIdentity(string $accessToken): ?array;

    public function getCampaignTiersAndBenefits(string $accessToken): PatreonPagedResponse;

    public function getCampaignMembers(string $accessToken): PatreonPagedResponse;

    /** @return array<string, mixed> */
    public function getAccessTokenFromRefreshToken(string $refreshToken): array;

    /** @return array<string, mixed> */
    public function getAccessTokenFromCode(string $code, string $redirectUrl): array;
}
