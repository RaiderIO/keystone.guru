<?php

namespace App\Service\Patreon\Dtos\Diagnostics;

/**
 * The campaign as the sync sees it: every tier, what it resolves to, and anything it cannot map.
 *
 * Answers "is the campaign itself configured in a way this code can handle?" in one call, without
 * needing a specific patron to look at.
 */
class PatreonCampaignDiagnostics
{
    /**
     * @param array<int, PatreonCampaignTierDiagnostics> $tiers
     * @param array<int, string>                         $knownBenefitTitles   Titles the campaign hands out that PatreonBenefit::ALL knows
     * @param array<int, string>                         $unknownBenefitTitles Titles it hands out that PatreonBenefit::ALL does not - each one blocks every member entitled to it
     */
    public function __construct(
        public readonly array $tiers,
        public readonly array $knownBenefitTitles,
        public readonly array $unknownBenefitTitles,
    ) {
    }

    /** @return array<int, PatreonCampaignTierDiagnostics> */
    public function getTiersGrantingNothing(): array
    {
        return array_values(array_filter($this->tiers, static fn(PatreonCampaignTierDiagnostics $tier) => $tier->grantsNothing()));
    }
}
