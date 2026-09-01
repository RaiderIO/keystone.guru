<?php

namespace App\Service\Patreon;

use App\Models\Patreon\PatreonManualGrant;
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

    /**
     * Manually grants a user every Patreon benefit, recording why and by whom. The grant overrides
     * whatever tier the hourly sync would otherwise compute for them, until it is revoked.
     */
    public function grantAllBenefits(User $user, User $grantedBy, string $reason): PatreonManualGrant;

    /**
     * Takes back a manual grant: removes the granted benefits, marks any audit record as revoked, and
     * cleans up the Patreon link if the admin panel fabricated it in the first place. A user with a
     * real Patreon link keeps that link, and the next patreon:refreshmembers run restores their true
     * tier.
     *
     * @return bool Whether anything was actually revoked.
     */
    public function revokeManualGrant(User $user, User $revokedBy): bool;
}
