<?php

namespace App\Service\Patreon\Logging;

use App\Logging\StructuredLogging;

class PatreonApiServiceLogging extends StructuredLogging implements PatreonApiServiceLoggingInterface
{

    /**
     * @return void
     */
    public function getIdentityStart(): void
    {
        $this->start(__METHOD__);
    }

    /**
     * @return void
     */
    public function getIdentityIncludedNotFound(): void
    {
        $this->error(__METHOD__);
    }

    /**
     * @param string $email
     * @return void
     */
    public function getIdentityUpdatedEmailAddress(string $email): void
    {
        $this->debug(__METHOD__, get_defined_vars());
    }

    /**
     * @param array $identityResponse
     * @return void
     */
    public function getIdentityEnd(array $identityResponse): void
    {
        $this->end(__METHOD__, get_defined_vars());
    }

    /**
     * @return void
     */
    public function getCampaignTiersAndBenefitsStart(): void
    {
        $this->start(__METHOD__);
    }

    /**
     * @param array|null $result
     * @return void
     */
    public function getCampaignTiersAndBenefitsEnd(?array $result): void
    {
        $this->end(__METHOD__, get_defined_vars());
    }

    /**
     * @return void
     */
    public function getCampaignMembersStart(): void
    {
        $this->start(__METHOD__);
    }

    /**
     * @param array|null $result
     * @return void
     */
    public function getCampaignMembersEnd(?array $result): void
    {
        $this->end(__METHOD__, get_defined_vars());
    }


    /**
     * @param int $count
     * @return void
     */
    public function getAllPagesPageNr(int $count): void
    {
        $this->debug(__METHOD__, get_defined_vars());
    }

    /**
     * @param $response
     * @return void
     */
    public function getAllPagesUnknownResponse($response): void
    {
        $this->error(__METHOD__, get_defined_vars());
    }


    /**
     * @param array $errors
     * @return void
     */
    public function getAllPagesError(array $errors): void
    {
        $this->error(__METHOD__, get_defined_vars());
    }
}
