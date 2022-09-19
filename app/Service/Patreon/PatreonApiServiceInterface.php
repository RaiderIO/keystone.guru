<?php

namespace App\Service\Patreon;

interface PatreonApiServiceInterface
{
    public function getIdentity(string $accessToken): ?array;

    public function getCampaignTiersAndBenefits(string $accessToken): ?array;

    public function getCampaignMembers(string $accessToken): ?array;

    public function getAccessTokenFromRefreshToken(string $refreshToken): array;

    public function getAccessTokenFromCode(string $code, string $redirectUrl): array;
}
