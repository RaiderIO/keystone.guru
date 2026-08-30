<?php

namespace App\Service\Patreon\Logging;

use App\Logging\Concerns\InteractsWithRollbar;
use App\Logging\StructuredLogging;
use App\Service\Patreon\Dtos\PatreonPagedResponse;

class PatreonApiServiceLogging extends StructuredLogging implements PatreonApiServiceLoggingInterface
{
    use InteractsWithRollbar;

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

    /**
     * @param array<string, mixed> $identityResponse
     */
    public function getIdentityEnd(array $identityResponse): void
    {
        $this->end(__METHOD__, get_defined_vars());
    }

    public function getCampaignTiersAndBenefitsStart(): void
    {
        $this->start(__METHOD__);
    }

    public function getCampaignTiersAndBenefitsEnd(?PatreonPagedResponse $result): void
    {
        $this->end(__METHOD__, self::describePagedResponse($result));
    }

    public function getCampaignMembersStart(): void
    {
        $this->start(__METHOD__);
    }

    public function getCampaignMembersEnd(?PatreonPagedResponse $result): void
    {
        $this->end(__METHOD__, self::describePagedResponse($result));
    }

    public function getAllPagesPageNr(int $count): void
    {
        $this->debug(__METHOD__, get_defined_vars());
    }

    /**
     * @param mixed $response
     */
    public function getAllPagesUnknownResponse($response): void
    {
        $this->error(__METHOD__, get_defined_vars());
    }

    /**
     * @param array<string, mixed> $errors
     */
    public function getAllPagesError(array $errors): void
    {
        $this->error(__METHOD__, get_defined_vars());
    }

    /**
     * The full response is far too large to log - what matters when diagnosing a sync is only how much
     * of it arrived, so log the shape rather than the contents (#4373).
     *
     * @return array<string, mixed>
     */
    private static function describePagedResponse(?PatreonPagedResponse $result): array
    {
        return [
            'pageCount' => $result?->pageCount,
            'rowCount'  => $result?->rowCount,
            'truncated' => $result?->truncated,
            'hasErrors' => $result?->hasErrors(),
        ];
    }
}
