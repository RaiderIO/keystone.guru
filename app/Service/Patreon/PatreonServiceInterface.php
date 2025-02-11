<?php

namespace App\Service\Patreon;

use App\Models\User;
use App\Service\Patreon\Dtos\LinkToUserIdResult;

interface PatreonServiceInterface
{
    public function loadCampaignBenefits(): ?array;

    public function loadCampaignTiers(): ?array;

    public function loadCampaignMembers(): ?array;

    public function applyPaidBenefitsForMember(array $campaignBenefits, array $campaignTiers, array $member): bool;

    public function linkToUserAccount(User $user, string $code, string $redirectUri): LinkToUserIdResult;
}
