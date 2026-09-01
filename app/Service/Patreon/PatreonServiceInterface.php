<?php

namespace App\Service\Patreon;

use App\Models\User;
use App\Service\Patreon\Dtos\ApplyPaidBenefitsForMemberResult;
use App\Service\Patreon\Dtos\LinkToUserIdResult;
use App\Service\Patreon\Dtos\PatreonCampaignMembers;
use App\Service\Patreon\Dtos\PatreonMemberSyncPlan;

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

    public function loadCampaignMembers(): ?PatreonCampaignMembers;

    /**
     * @param array<int, array<string, mixed>> $campaignBenefits
     * @param array<int, array<string, mixed>> $campaignTiers
     * @param array<string, mixed>             $member
     */
    public function applyPaidBenefitsForMember(array $campaignBenefits, array $campaignTiers, array $member): ApplyPaidBenefitsForMemberResult;

    /**
     * Works out what applyPaidBenefitsForMember() would do to this member, without writing anything.
     *
     * @param array<int, array<string, mixed>> $campaignBenefits
     * @param array<int, array<string, mixed>> $campaignTiers
     * @param array<string, mixed>             $member
     */
    public function planPaidBenefitsForMember(array $campaignBenefits, array $campaignTiers, array $member): PatreonMemberSyncPlan;

    public function linkToUserAccount(User $user, string $code, string $redirectUri): LinkToUserIdResult;
}
