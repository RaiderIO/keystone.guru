<?php

namespace App\Http\Resources\Patreon;

use App\Service\Patreon\Dtos\Diagnostics\PatreonCampaignDiagnostics;
use App\Service\Patreon\Dtos\Diagnostics\PatreonCampaignTierDiagnostics;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property PatreonCampaignDiagnostics $resource
 */
class PatreonCampaignDiagnosticsResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'data' => [
                'tiers' => array_map(static fn(PatreonCampaignTierDiagnostics $tier) => [
                    'tier_id'                => $tier->tierId,
                    'title'                  => $tier->title,
                    'benefit_titles'         => $tier->benefitTitles,
                    'unknown_benefit_titles' => $tier->unknownBenefitTitles,
                    'grants_nothing'         => $tier->grantsNothing(),
                ], $this->resource->tiers),
                'known_benefit_titles' => $this->resource->knownBenefitTitles,
                // Every member entitled to one of these is skipped entirely until it is added to
                // PatreonBenefit::ALL
                'unknown_benefit_titles'    => $this->resource->unknownBenefitTitles,
                'tier_ids_granting_nothing' => array_map(
                    static fn(PatreonCampaignTierDiagnostics $tier) => $tier->tierId,
                    $this->resource->getTiersGrantingNothing(),
                ),
            ],
        ];
    }
}
