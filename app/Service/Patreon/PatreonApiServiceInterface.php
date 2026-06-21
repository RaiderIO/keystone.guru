<?php

namespace App\Service\Patreon;

interface PatreonApiServiceInterface
{
    /**
     * @return array<string, mixed>|null
     */
    public function getIdentity(string $accessToken): ?array;

    /**
     * @return array<string, mixed>|null
     */
    public function getCampaignTiersAndBenefits(string $accessToken): ?array;

    /**
     * @return array<string, mixed>|null
     */
    public function getCampaignMembers(string $accessToken): ?array;

    /** @return array<string, mixed> */
    public function getAccessTokenFromRefreshToken(string $refreshToken): array;

    /** @return array<string, mixed> */
    public function getAccessTokenFromCode(string $code, string $redirectUrl): array;
}
