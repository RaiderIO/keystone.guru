<?php

namespace App\Service\Patreon\Logging;

interface PatreonApiServiceLoggingInterface
{
    public function getIdentityStart(): void;

    public function getIdentityIncludedNotFound(): void;

    public function getIdentityUpdatedEmailAddress(string $email): void;

    public function getIdentityEnd(array $identityResponse): void;

    public function getCampaignTiersAndBenefitsStart(): void;

    public function getCampaignTiersAndBenefitsEnd(?array $result): void;

    public function getCampaignMembersStart(): void;

    public function getCampaignMembersEnd(?array $result): void;

    public function getAllPagesPageNr(int $count): void;

    public function getAllPagesError(array $errors): void;
}
