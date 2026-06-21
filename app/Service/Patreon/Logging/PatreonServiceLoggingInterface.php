<?php

namespace App\Service\Patreon\Logging;

use App\Service\Patreon\Dtos\LinkToUserIdResult;
use Exception;

interface PatreonServiceLoggingInterface
{
    public function loadCampaignBenefitsAdminUserNull(): void;

    public function loadCampaignBenefitsStart(): void;

    /**
     * @param array<string, mixed> $tiersAndBenefitsResponse
     */
    public function loadCampaignBenefitsRetrieveTiersErrors(array $tiersAndBenefitsResponse): void;

    /**
     * @param array<string, mixed> $tiersAndBenefitsResponse
     */
    public function loadCampaignBenefitsRetrieveTiersIncludedNotSet(array $tiersAndBenefitsResponse): void;

    public function loadCampaignBenefitsEnd(): void;

    public function loadCampaignTiersAdminUserNull(): void;

    public function loadCampaignTiersStart(): void;

    /**
     * @param array<string, mixed> $tiersAndBenefitsResponse
     */
    public function loadCampaignTiersRetrieveTiersAndBenefitsErrors(array $tiersAndBenefitsResponse): void;

    /**
     * @param array<string, mixed> $tiersAndBenefitsResponse
     */
    public function loadCampaignTiersRetrieveMembersIncludedNotSet(array $tiersAndBenefitsResponse): void;

    public function loadCampaignTiersEnd(): void;

    public function loadCampaignMembersAdminUserNull(): void;

    public function loadCampaignMembersStart(): void;

    /**
     * @param array<string, mixed> $membersResponse
     */
    public function loadCampaignTiersRetrieveMembersErrors(array $membersResponse): void;

    /**
     * @param array<string, mixed> $membersResponse
     */
    public function loadCampaignTiersRetrieveMembersDataNotSet(array $membersResponse): void;

    public function loadCampaignMembersEnd(): void;

    public function applyPaidBenefitsForMemberEmptyMemberEmail(): void;

    public function applyPaidBenefitsForMemberStart(string $memberEmail): void;

    public function applyPaidBenefitsForMemberCannotFindPatreonData(): void;

    public function applyPaidBenefitsForMemberCannotFindUserForPatreonUserLink(): void;

    public function applyPaidBenefitsForMemberUserManuallyAssignedAllBenefits(): void;

    public function applyPaidBenefitsForMemberRemovedAllBenefits(): void;

    public function applyPaidBenefitsAddedPatreonBenefit(string $benefit, string $email): void;

    public function applyPaidBenefitsRevokedPatreonBenefit(string $removedBenefit, string $email): void;

    public function applyPaidBenefitsForMemberEnd(): void;

    public function linkToUserAccountStart(int $id, string $code, string $redirectUri): void;

    /**
     * @param array<string, mixed> $tokens
     */
    public function linkToUserAccountTokens(array $tokens): void;

    public function linkToUserAccountAdminUser(): void;

    /**
     * @param array<string, mixed>|null $identityResponse
     */
    public function linkToUserAccountIdentityResponse(?array $identityResponse): void;

    /**
     * @param array<int, mixed> $errors
     */
    public function linkToUserAccountIdentityError(array $errors): void;

    public function linkToUserAccountIdentityIncludedNotSet(): void;

    public function linkToUserAccountSessionExpired(): void;

    public function linkToUserAccountException(Exception $e): void;

    public function linkToUserAccountEnd(LinkToUserIdResult $result): void;

    public function loadAdminUserIsCached(int $id): void;

    public function loadAdminUserStart(): void;

    public function loadAdminUserAdminUserNotFound(): void;

    public function loadAdminUserPatreonUserLinkNotSet(): void;

    public function loadAdminUserTokenExpired(): void;

    /**
     * @param array<string, mixed> $errors
     */
    public function loadAdminUserTokenRefreshError(array $errors): void;

    /**
     * @param array<string, mixed> $tokens
     */
    public function loadAdminUserAccessTokenNotSet(array $tokens): void;

    /**
     * @param array<string, mixed> $tokens
     */
    public function loadAdminUserRefreshTokenNotSet(array $tokens): void;

    /**
     * @param array<string, mixed> $tokens
     */
    public function loadAdminUserExpiresInNotSet(array $tokens): void;

    public function loadAdminUserUpdatedTokenSuccessfully(string $expiresAt): void;

    public function loadAdminUserEnd(): void;

    public function createPatreonUserLinkSuccessful(int $userId, int $patreonUserLinkId): void;
}
