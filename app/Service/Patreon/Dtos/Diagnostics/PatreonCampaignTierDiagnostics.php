<?php

namespace App\Service\Patreon\Dtos\Diagnostics;

/**
 * One campaign tier as the sync resolves it, and whether that resolution is usable.
 */
class PatreonCampaignTierDiagnostics
{
    /**
     * @param array<int, string> $benefitTitles        Benefit titles the tier grants
     * @param array<int, string> $unknownBenefitTitles Those of them missing from PatreonBenefit::ALL
     */
    public function __construct(
        public readonly string $tierId,
        public readonly string $title,
        public readonly array  $benefitTitles,
        public readonly array  $unknownBenefitTitles,
    ) {
    }

    public function grantsNothing(): bool
    {
        return $this->benefitTitles === [];
    }
}
