<?php

namespace App\Service\Patreon\Logging;

use App\Logging\StructuredLogging;

class PatreonServiceLogging extends StructuredLogging implements PatreonServiceLoggingInterface
{
    /**
     * @return void
     */
    public function loadCampaignBenefitsAdminUserNull(): void
    {
        $this->error(__METHOD__);
    }

    /**
     * @return void
     */
    public function loadCampaignBenefitsStart(): void
    {
        $this->start(__METHOD__);
    }

    /**
     * @param array $tiersAndBenefitsResponse
     * @return void
     */
    public function loadCampaignBenefitsRetrieveTiersErrors(array $tiersAndBenefitsResponse): void
    {
        $this->error(__METHOD__, get_defined_vars());
    }

    /**
     * @return void
     */
    public function loadCampaignBenefitsEnd(): void
    {
        $this->end(__METHOD__);
    }

    /**
     * @return void
     */
    public function loadCampaignTiersAdminUserNull(): void
    {
        $this->error(__METHOD__, get_defined_vars());
    }

    /**
     * @return void
     */
    public function loadCampaignTiersStart(): void
    {
        $this->start(__METHOD__);
    }

    /**
     * @param array $tiersAndBenefitsResponse
     * @return void
     */
    public function loadCampaignTiersRetrieveTiersAndBenefitsErrors(array $tiersAndBenefitsResponse): void
    {
        $this->error(__METHOD__, get_defined_vars());
    }

    /**
     * @return void
     */
    public function loadCampaignTiersEnd(): void
    {
        $this->end(__METHOD__);
    }

    /**
     * @return void
     */
    public function loadCampaignMembersAdminUserNull(): void
    {
        $this->error(__METHOD__, get_defined_vars());
    }

    /**
     * @return void
     */
    public function loadCampaignMembersStart(): void
    {
        $this->start(__METHOD__);
    }

    /**
     * @param array $membersResponse
     * @return void
     */
    public function loadCampaignTiersRetrieveMembersErrors(array $membersResponse): void
    {
        $this->error(__METHOD__, get_defined_vars());
    }

    /**
     * @return void
     */
    public function loadCampaignMembersEnd(): void
    {
        $this->end(__METHOD__);
    }

    /**
     * @return void
     */
    public function applyPaidBenefitsForMemberEmptyMemberEmail(): void
    {
        $this->info(__METHOD__, get_defined_vars());
    }

    /**
     * @param string $memberEmail
     * @return void
     */
    public function applyPaidBenefitsForMemberStart(string $memberEmail): void
    {
        $this->start(__METHOD__, get_defined_vars());
    }

    /**
     * @return void
     */
    public function applyPaidBenefitsForMemberCannotFindPatreonData(): void
    {
        $this->debug(__METHOD__, get_defined_vars());
    }

    /**
     * @return void
     */
    public function applyPaidBenefitsForMemberCannotFindUserForPatreonUserLink(): void
    {
        $this->error(__METHOD__, get_defined_vars());
    }

    /**
     * @return void
     */
    public function applyPaidBenefitsForMemberUserManuallyAssignedAllBenefits(): void
    {
        $this->debug(__METHOD__, get_defined_vars());
    }

    /**
     * @return void
     */
    public function applyPaidBenefitsForMemberRemovedAllBenefits(): void
    {
        $this->info(__METHOD__, get_defined_vars());
    }

    /**
     * @param string $benefit
     * @param string $email
     * @return void
     */
    public function applyPaidBenefitsAddedPatreonBenefit(string $benefit, string $email): void
    {
        $this->info(__METHOD__, get_defined_vars());
    }

    /**
     * @param string $removedBenefit
     * @param string $email
     * @return void
     */
    public function applyPaidBenefitsRevokedPatreonBenefit(string $removedBenefit, string $email): void
    {
        $this->info(__METHOD__, get_defined_vars());
    }

    /**
     * @return void
     */
    public function applyPaidBenefitsForMemberEnd(): void
    {
        $this->end(__METHOD__);
    }

    /**
     * @param int $id
     * @return void
     */
    public function loadAdminUserIsCached(int $id): void
    {
        $this->debug(__METHOD__, get_defined_vars());
    }

    /**
     * @return void
     */
    public function loadAdminUserStart(): void
    {
        $this->start(__METHOD__);
    }

    /**
     * @return void
     */
    public function loadAdminUserAdminUserNotFound(): void
    {
        $this->error(__METHOD__, get_defined_vars());
    }

    /**
     * @return void
     */
    public function loadAdminUserPatreonUserLinkNotSet(): void
    {
        $this->error(__METHOD__, get_defined_vars());
    }

    /**
     * @return void
     */
    public function loadAdminUserTokenExpired(): void
    {
        $this->debug(__METHOD__, get_defined_vars());
    }

    /**
     * @param array $errors
     * @return void
     */
    public function loadAdminUserTokenRefreshError(array $errors): void
    {
        $this->error(__METHOD__, get_defined_vars());
    }

    /**
     * @param array $tokens
     * @return void
     */
    public function loadAdminUserAccessTokenNotSet(array $tokens): void
    {
        $this->error(__METHOD__, get_defined_vars());
    }

    /**
     * @param array $tokens
     * @return void
     */
    public function loadAdminUserRefreshTokenNotSet(array $tokens): void
    {
        $this->error(__METHOD__, get_defined_vars());
    }

    /**
     * @param array $tokens
     * @return void
     */
    public function loadAdminUserExpiresInNotSet(array $tokens): void
    {
        $this->error(__METHOD__, get_defined_vars());
    }

    /**
     * @param bool $date
     * @return void
     */
    public function loadAdminUserUpdatedTokenSuccessfully(bool $date): void
    {
        $this->debug(__METHOD__, get_defined_vars());
    }

    /**
     * @return void
     */
    public function loadAdminUserEnd(): void
    {
        $this->end(__METHOD__);
    }

}
