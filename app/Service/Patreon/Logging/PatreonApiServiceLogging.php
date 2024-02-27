<?php

namespace App\Service\Patreon\Logging;

use App\Logging\StructuredLogging;

class PatreonApiServiceLogging extends StructuredLogging implements PatreonApiServiceLoggingInterface
{
    public function getIdentityStart(): void
    {
        $this->start(__METHOD__);
    }

    public function getIdentityIncludedNotFound(): void
    {
        $this->error(__METHOD__);
    }

    public function getIdentityUpdatedEmailAddress(string $email): void
    {
        $this->debug(__METHOD__, get_defined_vars());
    }

    public function getIdentityEnd(array $identityResponse): void
    {
        $this->end(__METHOD__, get_defined_vars());
    }

    public function getCampaignTiersAndBenefitsStart(): void
    {
        $this->start(__METHOD__);
    }

    public function getCampaignTiersAndBenefitsEnd(?array $result): void
    {
        $this->end(__METHOD__, get_defined_vars());
    }

    public function getCampaignMembersStart(): void
    {
        $this->start(__METHOD__);
    }

    public function getCampaignMembersEnd(?array $result): void
    {
        $this->end(__METHOD__, get_defined_vars());
    }

    public function getAllPagesPageNr(int $count): void
    {
        $this->debug(__METHOD__, get_defined_vars());
    }

    public function getAllPagesUnknownResponse($response): void
    {
        $this->error(__METHOD__, get_defined_vars());
    }

    public function getAllPagesError(array $errors): void
    {
        $this->error(__METHOD__, get_defined_vars());
    }
}
