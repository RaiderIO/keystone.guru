<?php

namespace App\Service\Patreon\Logging;

use App\Logging\RollbarStructuredLogging;

class PatreonServiceLogging extends RollbarStructuredLogging implements PatreonServiceLoggingInterface
{
    public function loadCampaignBenefitsAdminUserNull(): void
    {
        $this->error(__METHOD__);
    }

    public function loadCampaignBenefitsStart(): void
    {
        $this->start(__METHOD__);
    }

    public function loadCampaignBenefitsRetrieveTiersErrors(array $tiersAndBenefitsResponse): void
    {
        $this->error(__METHOD__, get_defined_vars());
    }

    public function loadCampaignBenefitsEnd(): void
    {
        $this->end(__METHOD__);
    }

    public function loadCampaignTiersAdminUserNull(): void
    {
        $this->error(__METHOD__, get_defined_vars());
    }

    public function loadCampaignTiersStart(): void
    {
        $this->start(__METHOD__);
    }

    public function loadCampaignTiersRetrieveTiersAndBenefitsErrors(array $tiersAndBenefitsResponse): void
    {
        $this->error(__METHOD__, get_defined_vars());
    }

    public function loadCampaignTiersEnd(): void
    {
        $this->end(__METHOD__);
    }

    public function loadCampaignMembersAdminUserNull(): void
    {
        $this->error(__METHOD__, get_defined_vars());
    }

    public function loadCampaignMembersStart(): void
    {
        $this->start(__METHOD__);
    }

    public function loadCampaignTiersRetrieveMembersErrors(array $membersResponse): void
    {
        $this->error(__METHOD__, get_defined_vars());
    }

    public function loadCampaignMembersEnd(): void
    {
        $this->end(__METHOD__);
    }

    public function applyPaidBenefitsForMemberEmptyMemberEmail(): void
    {
        $this->info(__METHOD__, get_defined_vars());
    }

    public function applyPaidBenefitsForMemberStart(string $memberEmail): void
    {
        $this->start(__METHOD__, get_defined_vars());
    }

    public function applyPaidBenefitsForMemberCannotFindPatreonData(): void
    {
        $this->debug(__METHOD__, get_defined_vars());
    }

    public function applyPaidBenefitsForMemberCannotFindUserForPatreonUserLink(): void
    {
        $this->error(__METHOD__, get_defined_vars());
    }

    public function applyPaidBenefitsForMemberUserManuallyAssignedAllBenefits(): void
    {
        $this->debug(__METHOD__, get_defined_vars());
    }

    public function applyPaidBenefitsForMemberRemovedAllBenefits(): void
    {
        $this->info(__METHOD__, get_defined_vars());
    }

    public function applyPaidBenefitsAddedPatreonBenefit(string $benefit, string $email): void
    {
        $this->info(__METHOD__, get_defined_vars());
    }

    public function applyPaidBenefitsRevokedPatreonBenefit(string $removedBenefit, string $email): void
    {
        $this->info(__METHOD__, get_defined_vars());
    }

    public function applyPaidBenefitsForMemberEnd(): void
    {
        $this->end(__METHOD__);
    }

    public function loadAdminUserIsCached(int $id): void
    {
        $this->debug(__METHOD__, get_defined_vars());
    }

    public function loadAdminUserStart(): void
    {
        $this->start(__METHOD__);
    }

    public function loadAdminUserAdminUserNotFound(): void
    {
        $this->error(__METHOD__, get_defined_vars());
    }

    public function loadAdminUserPatreonUserLinkNotSet(): void
    {
        $this->error(__METHOD__, get_defined_vars());
    }

    public function loadAdminUserTokenExpired(): void
    {
        $this->debug(__METHOD__, get_defined_vars());
    }

    public function loadAdminUserTokenRefreshError(array $errors): void
    {
        $this->error(__METHOD__, get_defined_vars());
    }

    public function loadAdminUserAccessTokenNotSet(array $tokens): void
    {
        $this->error(__METHOD__, get_defined_vars());
    }

    public function loadAdminUserRefreshTokenNotSet(array $tokens): void
    {
        $this->error(__METHOD__, get_defined_vars());
    }

    public function loadAdminUserExpiresInNotSet(array $tokens): void
    {
        $this->error(__METHOD__, get_defined_vars());
    }

    public function loadAdminUserUpdatedTokenSuccessfully(bool $date): void
    {
        $this->debug(__METHOD__, get_defined_vars());
    }

    public function loadAdminUserEnd(): void
    {
        $this->end(__METHOD__);
    }
}
