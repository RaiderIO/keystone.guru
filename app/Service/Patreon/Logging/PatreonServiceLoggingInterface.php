<?php

namespace App\Service\Patreon\Logging;

interface PatreonServiceLoggingInterface
{
    public function loadCampaignBenefitsAdminUserNull(): void;

    public function loadCampaignBenefitsStart(): void;

    public function loadCampaignBenefitsRetrieveTiersErrors(array $tiersAndBenefitsResponse): void;

    public function loadCampaignBenefitsEnd(): void;

    public function loadCampaignTiersAdminUserNull(): void;

    public function loadCampaignTiersStart(): void;

    public function loadCampaignTiersRetrieveTiersAndBenefitsErrors(array $tiersAndBenefitsResponse): void;

    public function loadCampaignTiersEnd(): void;

    public function loadCampaignMembersAdminUserNull(): void;

    public function loadCampaignMembersStart(): void;

    public function loadCampaignTiersRetrieveMembersErrors(array $membersResponse): void;

    public function loadCampaignMembersEnd(): void;

    public function applyPaidBenefitsForMemberEmptyMemberEmail(): void;

    public function applyPaidBenefitsForMemberStart(string $memberEmail): void;

    public function applyPaidBenefitsForMemberCannotFindPatreonData(): void;

    public function applyPaidBenefitsForMemberCannotFindUserForPatreonUserLink(): void;

    public function applyPaidBenefitsForMemberRemovedAllBenefits(): void;

    public function applyPaidBenefitsAddedPatreonBenefit(string $benefit, string $email): void;

    public function applyPaidBenefitsRevokedPatreonBenefit(string $removedBenefit, string $email): void;

    public function applyPaidBenefitsForMemberEnd(): void;

    public function loadAdminUserIsCached(int $id): void;

    public function loadAdminUserStart(): void;

    public function loadAdminUserAdminUserNotFound(): void;

    public function loadAdminUserPatreonUserLinkNotSet(): void;

    public function loadAdminUserTokenExpired(): void;

    public function loadAdminUserTokenRefreshError(array $errors): void;

    public function loadAdminUserAccessTokenNotSet(array $tokens): void;

    public function loadAdminUserRefreshTokenNotSet(array $tokens): void;

    public function loadAdminUserExpiresInNotSet(array $tokens): void;

    public function loadAdminUserUpdatedTokenSuccessfully(bool $date): void;

    public function loadAdminUserEnd(): void;
}
