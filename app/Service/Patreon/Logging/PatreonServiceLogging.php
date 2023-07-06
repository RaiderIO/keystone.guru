<?php

namespace App\Service\Patreon\Logging;

use App\Logging\StructuredLogging;

class PatreonServiceLogging extends StructuredLogging implements PatreonServiceLoggingInterface
{
    /**
     * @return mixed
     */
    public function loadCampaignBenefitsAdminUserNull(): void
    {
        $this->error(__METHOD__);
    }

    /**
     * @return mixed
     */
    public function loadCampaignBenefitsStart(): void
    {
        $this->start(__METHOD__);
    }

    /**
     * @param array $tiersAndBenefitsResponse
     * @return mixed
     */
    public function loadCampaignBenefitsRetrieveTiersErrors(array $tiersAndBenefitsResponse): void
    {
        $this->error(__METHOD__, get_defined_vars());
    }

    /**
     * @return mixed
     */
    public function loadCampaignBenefitsEnd(): void
    {
        $this->end(__METHOD__);
    }

    /**
     * @return mixed
     */
    public function loadCampaignTiersAdminUserNull(): void
    {
        $this->error(__METHOD__, get_defined_vars());
    }

    /**
     * @return mixed
     */
    public function loadCampaignTiersStart(): void
    {
        $this->start(__METHOD__);
    }

    /**
     * @param array $tiersAndBenefitsResponse
     * @return mixed
     */
    public function loadCampaignTiersRetrieveTiersAndBenefitsErrors(array $tiersAndBenefitsResponse): void
    {
        $this->error(__METHOD__, get_defined_vars());
    }

    /**
     * @return mixed
     */
    public function loadCampaignTiersEnd(): void
    {
        $this->end(__METHOD__);
    }

    /**
     * @return mixed
     */
    public function loadCampaignMembersAdminUserNull(): void
    {
        $this->error(__METHOD__, get_defined_vars());
    }

    /**
     * @return mixed
     */
    public function loadCampaignMembersStart(): void
    {
        $this->start(__METHOD__);
    }

    /**
     * @param array $membersResponse
     * @return mixed
     */
    public function loadCampaignTiersRetrieveMembersErrors(array $membersResponse): void
    {
        $this->error(__METHOD__, get_defined_vars());
    }

    /**
     * @return mixed
     */
    public function loadCampaignMembersEnd(): void
    {
        $this->end(__METHOD__);
    }

    /**
     * @return mixed
     */
    public function applyPaidBenefitsForMemberEmptyMemberEmail(): void
    {
        $this->info(__METHOD__, get_defined_vars());
    }

    /**
     * @param string $memberEmail
     * @return mixed
     */
    public function applyPaidBenefitsForMemberStart(string $memberEmail): void
    {
        $this->start(__METHOD__, get_defined_vars());
    }

    /**
     * @return mixed
     */
    public function applyPaidBenefitsForMemberCannotFindPatreonData(): void
    {
        $this->debug(__METHOD__, get_defined_vars());
    }

    /**
     * @return mixed
     */
    public function applyPaidBenefitsForMemberCannotFindUserForPatreonUserLink(): void
    {
        $this->error(__METHOD__, get_defined_vars());
    }

    /**
     * @return mixed
     */
    public function applyPaidBenefitsForMemberRemovedAllBenefits(): void
    {
        $this->info(__METHOD__, get_defined_vars());
    }

    /**
     * @param string $benefit
     * @param string $email
     * @return mixed
     */
    public function applyPaidBenefitsAddedPatreonBenefit(string $benefit, string $email): void
    {
        $this->info(__METHOD__, get_defined_vars());
    }

    /**
     * @param string $removedBenefit
     * @param string $email
     * @return mixed
     */
    public function applyPaidBenefitsRevokedPatreonBenefit(string $removedBenefit, string $email): void
    {
        $this->info(__METHOD__, get_defined_vars());
    }

    /**
     * @return mixed
     */
    public function applyPaidBenefitsForMemberEnd(): void
    {
        $this->end(__METHOD__);
    }

    /**
     * @param int $id
     * @return mixed
     */
    public function loadAdminUserIsCached(int $id): void
    {
        $this->debug(__METHOD__, get_defined_vars());
    }

    /**
     * @return mixed
     */
    public function loadAdminUserStart(): void
    {
        $this->start(__METHOD__);
    }

    /**
     * @return mixed
     */
    public function loadAdminUserAdminUserNotFound(): void
    {
        $this->error(__METHOD__, get_defined_vars());
    }

    /**
     * @return mixed
     */
    public function loadAdminUserPatreonUserLinkNotSet(): void
    {
        $this->error(__METHOD__, get_defined_vars());
    }

    /**
     * @return mixed
     */
    public function loadAdminUserTokenExpired(): void
    {
        $this->debug(__METHOD__, get_defined_vars());
    }

    /**
     * @param array $errors
     * @return mixed
     */
    public function loadAdminUserTokenRefreshError(array $errors): void
    {
        $this->error(__METHOD__, get_defined_vars());
    }

    /**
     * @param array $tokens
     * @return mixed
     */
    public function loadAdminUserAccessTokenNotSet(array $tokens): void
    {
        $this->error(__METHOD__, get_defined_vars());
    }

    /**
     * @param array $tokens
     * @return mixed
     */
    public function loadAdminUserRefreshTokenNotSet(array $tokens): void
    {
        $this->error(__METHOD__, get_defined_vars());
    }

    /**
     * @param array $tokens
     * @return mixed
     */
    public function loadAdminUserExpiresInNotSet(array $tokens): void
    {
        $this->error(__METHOD__, get_defined_vars());
    }

    /**
     * @param bool $date
     * @return mixed
     */
    public function loadAdminUserUpdatedTokenSuccessfully(bool $date): void
    {
        $this->debug(__METHOD__, get_defined_vars());
    }

    /**
     * @return mixed
     */
    public function loadAdminUserEnd(): void
    {
        $this->end(__METHOD__);
    }

}
