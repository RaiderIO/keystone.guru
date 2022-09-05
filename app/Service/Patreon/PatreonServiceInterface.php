<?php

namespace App\Service\Patreon;

interface PatreonServiceInterface
{
    public function loadCampaignBenefits(PatreonApiService $patreonApiService): ?array;

    public function loadCampaignTiers(PatreonApiService $patreonApiService): ?array;

    public function loadCampaignMembers(PatreonApiService $patreonApiService): ?array;

    public function applyPaidBenefitsForMember(array $campaignBenefits, array $campaignTiers, array $member): bool;
}
