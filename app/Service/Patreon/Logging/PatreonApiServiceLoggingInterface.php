<?php

namespace App\Service\Patreon\Logging;

interface PatreonApiServiceLoggingInterface
{
    public function getIdentityStart(): void;

    public function getIdentityIncludedNotFound(): void;

    public function getIdentityUpdatedEmailAddress(string $email): void;

    /**
     * @param array<string, mixed> $identityResponse
     */
    public function getIdentityEnd(array $identityResponse): void;

    public function getCampaignTiersAndBenefitsStart(): void;

    /**
     * @param array<string, mixed>|null $result
     */
    public function getCampaignTiersAndBenefitsEnd(?array $result): void;

    public function getCampaignMembersStart(): void;

    /**
     * @param array<string, mixed>|null $result
     */
    public function getCampaignMembersEnd(?array $result): void;

    public function getAllPagesPageNr(int $count): void;

    /**
     * @param mixed $response
     */
    public function getAllPagesUnknownResponse($response): void;

    /**
     * @param array<string, mixed> $errors
     */
    public function getAllPagesError(array $errors): void;
}
