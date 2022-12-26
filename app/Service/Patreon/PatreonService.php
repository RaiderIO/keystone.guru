<?php

namespace App\Service\Patreon;

use App\Models\Patreon\PatreonBenefit;
use App\Models\Patreon\PatreonUserLink;
use App\Models\Patreon\PatreonUserBenefit;
use App\Service\Patreon\Logging\PatreonServiceLoggingInterface;
use App\User;

class PatreonService implements PatreonServiceInterface
{
    /** @var User|null */
    private ?User $cachedAdminUser = null;

    /** @var PatreonServiceLoggingInterface */
    private PatreonServiceLoggingInterface $log;

    /**
     * @param PatreonServiceLoggingInterface $log
     */
    public function __construct(PatreonServiceLoggingInterface $log)
    {
        $this->log = $log;
    }


    /**
     * @param PatreonApiService $patreonApiService
     * @return array{array{id: int, type: string, attributes: array{title: string}}}|null
     */
    public function loadCampaignBenefits(PatreonApiService $patreonApiService): ?array
    {
        if (($adminUser = $this->loadAdminUser($patreonApiService)) === null) {
            $this->log->loadCampaignBenefitsAdminUserNull();
            return null;
        }

        try {
            $this->log->loadCampaignBenefitsStart();

            // Fetch the tiers and benefits of a campaign
            $tiersAndBenefitsResponse = $patreonApiService->getCampaignTiersAndBenefits($adminUser->patreonUserLink->access_token);
            if (isset($tiersAndBenefitsResponse['errors'])) {
                $this->log->loadCampaignBenefitsRetrieveTiersErrors($tiersAndBenefitsResponse);
                return null;
            }

            return collect($tiersAndBenefitsResponse['included'])->filter(function ($included) {
                return $included['type'] === 'benefit';
            })->toArray();
        } finally {
            $this->log->loadCampaignBenefitsEnd();
        }
    }

    /**
     * @param PatreonApiService $patreonApiService
     * @return array{array{id: int, type: string, relationships: array}}|null
     */
    public function loadCampaignTiers(PatreonApiService $patreonApiService): ?array
    {
        if (($adminUser = $this->loadAdminUser($patreonApiService)) === null) {
            $this->log->loadCampaignTiersAdminUserNull();
            return null;
        }


        try {
            $this->log->loadCampaignTiersStart();

            // Fetch the tiers and benefits of a campaign
            $tiersAndBenefitsResponse = $patreonApiService->getCampaignTiersAndBenefits($adminUser->patreonUserLink->access_token);
            if (isset($tiersAndBenefitsResponse['errors'])) {
                $this->log->loadCampaignTiersRetrieveTiersAndBenefitsErrors($tiersAndBenefitsResponse);
                return null;
            }

            return collect($tiersAndBenefitsResponse['included'])->filter(function ($included) {
                return $included['type'] === 'tier';
            })->toArray();
        } finally {
            $this->log->loadCampaignTiersEnd();
        }
    }

    /**
     * @param PatreonApiService $patreonApiService
     * @return array|null
     */
    public function loadCampaignMembers(PatreonApiService $patreonApiService): ?array
    {
        if (($adminUser = $this->loadAdminUser($patreonApiService)) === null) {
            $this->log->loadCampaignMembersAdminUserNull();
            return null;
        }


        try {
            $this->log->loadCampaignMembersStart();

            // Now that we have a valid token - perform the members request
            $membersResponse = $patreonApiService->getCampaignMembers($adminUser->patreonUserLink->access_token);
            if (isset($membersResponse['errors'])) {
                $this->log->loadCampaignTiersRetrieveMembersErrors($membersResponse);
                return null;
            }

            return collect($membersResponse['data'])->filter(function ($included) {
                return $included['type'] === 'member';
            })->toArray();
        } finally {
            $this->log->loadCampaignMembersEnd();
        }
    }


    /**
     * @param array $campaignBenefits
     * @param array $campaignTiers
     * @param array $member
     * @return bool
     */
    public function applyPaidBenefitsForMember(array $campaignBenefits, array $campaignTiers, array $member): bool
    {
        /** @var array{id: string, type: string, relationships: array, attributes: array{email: string}} $member */
        $memberEmail = $member['attributes']['email'];

        if (empty($memberEmail)) {
            $this->log->applyPaidBenefitsForMemberEmptyMemberEmail();
            return false;
        }

        /** @var PatreonUserLink $patreonUserLink */
        $patreonUserLink = PatreonUserLink::with(['user'])->where('email', $memberEmail)->first();
        try {
            $this->log->applyPaidBenefitsForMemberStart($memberEmail);


            if ($patreonUserLink === null) {
                $this->log->applyPaidBenefitsForMemberCannotFindPatreonData();
                return false;
            }

            $user = $patreonUserLink->user;
            if ($user === null) {
                $this->log->applyPaidBenefitsForMemberCannotFindUserForPatreonUserLink();
                return false;
            }

            // We now know which user this is - update the benefits of this user
            $newBenefits = collect();
            foreach ($member['relationships']['currently_entitled_tiers']['data'] as $currentlyEntitledTier) {
                /** @var $currentlyEntitledTier array{id: int, type: string} */
                // For all tiers this user is paying for - combine the benefits to one big array
                $newBenefits = $newBenefits->merge($this->getBenefitsByTierId($campaignTiers, $campaignBenefits, $currentlyEntitledTier['id']));
            }

            // If the user has no benefits (maybe user unsubbed or didn't pay up)
            if ($newBenefits->isEmpty()) {
                // Remove all their tiers
                $user->patreonUserLink->patreonuserbenefits()->delete();
                $this->log->applyPaidBenefitsForMemberRemovedAllBenefits();
            } else {
                // Update the patreon benefits to their new status
                foreach ($newBenefits as $benefit) {
                    if (!$user->hasPatreonBenefit($benefit)) {
                        PatreonUserBenefit::create([
                            'patreon_user_link_id' => $user->patreon_user_link_id,
                            'patreon_benefit_id'   => PatreonBenefit::ALL[$benefit],
                        ]);
                        $this->log->applyPaidBenefitsAddedPatreonBenefit($benefit, $user->email);
                    }
                }

                // Check if any patreon benefits were removed, if so delete them
                $removedBenefits = $user->getPatreonBenefits()->diff($newBenefits);
                if ($removedBenefits->isNotEmpty()) {
                    foreach ($removedBenefits as $removedBenefit) {
                        PatreonUserBenefit::where('patreon_user_link_id', $user->patreon_user_link_id)
                            ->where('patreon_benefit_id', PatreonBenefit::ALL[$removedBenefit])
                            ->delete();

                        $this->log->applyPaidBenefitsRevokedPatreonBenefit($removedBenefit, $user->email);
                    }
                }
            }
        } finally {
            $this->log->applyPaidBenefitsForMemberEnd($memberEmail);
        }

        return true;
    }


    /**
     * @param PatreonApiService $patreonApiService
     * @return User|null
     */
    private function loadAdminUser(PatreonApiService $patreonApiService): ?User
    {
        if (isset($this->cachedAdminUser)) {
            $this->log->loadAdminUserIsCached($this->cachedAdminUser->id);
            return $this->cachedAdminUser;
        }

        try {
            $this->log->loadAdminUserStart();

            // Admin is always user ID 1
            $adminUser = User::find(1);

            if ($adminUser === null) {
                $this->log->loadAdminUserAdminUserNotFound();
                return null;
            }

            // Check if admin was setup correctly
            $adminUser->load(['patreonUserLink']);
            if ($adminUser->patreonUserLink === null) {
                $this->log->loadAdminUserPatreonUserLinkNotSet();
                return null;
            }

            // Check if token is expired, if so refresh it
            if ($adminUser->patreonUserLink->isExpired()) {
                $this->log->loadAdminUserTokenExpired();
                $tokens = $patreonApiService->getAccessTokenFromRefreshToken($adminUser->patreonUserLink->refresh_token);

                if (isset($tokens['errors'])) {
                    $this->log->loadAdminUserTokenRefreshError($tokens);
                    return null;
                } else if (!isset($tokens['access_token'])) {
                    $this->log->loadAdminUserAccessTokenNotSet($tokens);
                    return null;
                } else if (!isset($tokens['refresh_token'])) {
                    $this->log->loadAdminUserRefreshTokenNotSet($tokens);
                    return null;
                } else if (!isset($tokens['expires_in'])) {
                    $this->log->loadAdminUserExpiresInNotSet($tokens);
                    return null;
                } else {
                    $adminUser->patreonUserLink->update([
                        'access_token'  => $tokens['access_token'],
                        'refresh_token' => $tokens['refresh_token'],
                        'expires_at'    => date('Y-m-d H:i:s', time() + $tokens['expires_in']),
                    ]);

                    $this->log->loadAdminUserUpdatedTokenSuccessfully(date('Y-m-d H:i:s', time() + $tokens['expires_in']));
                }
            }

            return $this->cachedAdminUser = $adminUser;
        } finally {
            $this->log->loadAdminUserEnd();
        }
    }


    /**
     * @param array $campaignTiers
     * @param array $campaignBenefits
     * @param int $tierId
     * @return array|null
     */
    private function getBenefitsByTierId(array $campaignTiers, array $campaignBenefits, int $tierId): ?array
    {
        $result = [];

        foreach ($campaignTiers as $tier) {
            if ((int)$tier['id'] === $tierId) {
                // Found the tier, now match the benefits..
                foreach ($tier['relationships']['benefits']['data'] as $benefitData) {
                    /** @var $benefitData {array: id: string, type: string} */

                    // Search the list of benefits for a match, and if found add the title to the result array
                    foreach ($campaignBenefits as $campaignBenefit) {
                        if ($campaignBenefit['id'] === $benefitData['id']) {
                            $result[] = $campaignBenefit['attributes']['title'];
                            break;
                        }
                    }
                }
                break;
            }
        }

        return $result;
    }
}
