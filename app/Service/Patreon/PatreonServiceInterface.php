<?php

namespace App\Service\Patreon;

use App\Models\User;
use App\Service\Patreon\Dtos\LinkToUserIdResult;

interface PatreonServiceInterface
{
    /**
     * @return array<int, array<string, mixed>>|null
     */
    public function loadCampaignBenefits(): ?array;

    /**
     * @return array<int, array<string, mixed>>|null
     */
    public function loadCampaignTiers(): ?array;

    /**
     * @return array<int, array<string, mixed>>|null
     */
    public function loadCampaignMembers(): ?array;

    /**
     * @param array<int, array<string, mixed>> $campaignBenefits
     * @param array<int, array<string, mixed>> $campaignTiers
     * @param array<string, mixed>             $member
     */
    public function applyPaidBenefitsForMember(array $campaignBenefits, array $campaignTiers, array $member): bool;

    public function linkToUserAccount(User $user, string $code, string $redirectUri): LinkToUserIdResult;
}
