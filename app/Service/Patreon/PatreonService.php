<?php

namespace App\Service\Patreon;

use App\Models\Patreon\PatreonBenefit;
use App\Models\Patreon\PatreonUserLink;
use App\Models\Patreon\PatreonUserBenefit;
use App\User;

class PatreonService implements PatreonServiceInterface
{
    /** @var User|null */
    private ?User $cachedAdminUser = null;

    /**
     * @param PatreonApiService $patreonApiService
     * @return array{array{id: int, type: string, attributes: array{title: string}}}|null
     */
    public function loadCampaignBenefits(PatreonApiService $patreonApiService): ?array
    {
        if (($adminUser = $this->loadAdminUser($patreonApiService)) === null) {
            return null;
        }

        // Fetch the tiers and benefits of a campaign
        $tiersAndBenefitsResponse = $patreonApiService->getCampaignTiersAndBenefits($adminUser->patreonUserLink->access_token);
        if (isset($tiersAndBenefitsResponse['errors'])) {
            logger()->error('Error retrieving tiers!');
            logger()->warning(json_encode($tiersAndBenefitsResponse));
            return null;
        }

        return collect($tiersAndBenefitsResponse['included'])->filter(function ($included) {
            return $included['type'] === 'benefit';
        })->toArray();
    }

    /**
     * @param PatreonApiService $patreonApiService
     * @return array{array{id: int, type: string, relationships: array}}|null
     */
    public function loadCampaignTiers(PatreonApiService $patreonApiService): ?array
    {
        if (($adminUser = $this->loadAdminUser($patreonApiService)) === null) {
            return null;
        }

        // Fetch the tiers and benefits of a campaign
        $tiersAndBenefitsResponse = $patreonApiService->getCampaignTiersAndBenefits($adminUser->patreonUserLink->access_token);
        if (isset($tiersAndBenefitsResponse['errors'])) {
            logger()->error('Error retrieving tiers!');
            logger()->warning(json_encode($tiersAndBenefitsResponse));
            return null;
        }

        return collect($tiersAndBenefitsResponse['included'])->filter(function ($included) {
            return $included['type'] === 'tier';
        })->toArray();
    }

    /**
     * @param PatreonApiService $patreonApiService
     * @return array|null
     */
    public function loadCampaignMembers(PatreonApiService $patreonApiService): ?array
    {
        if (($adminUser = $this->loadAdminUser($patreonApiService)) === null) {
            return null;
        }

        // Now that we have a valid token - perform the members request
        $membersResponse = $patreonApiService->getCampaignMembers($adminUser->patreonUserLink->access_token);
        if (isset($membersResponse['errors'])) {
            logger()->error('Error retrieving members!');
            logger()->warning(json_encode($membersResponse));
            return null;
        }

        return collect($membersResponse['data'])->filter(function ($included) {
            return $included['type'] === 'member';
        })->toArray();
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
            logger()->debug('Cannot find patreon data for empty e-mail');
            return false;
        }

        /** @var PatreonUserLink $patreonUserLink */
        $patreonUserLink = PatreonUserLink::with(['user'])->where('email', $memberEmail)->first();

        if ($patreonUserLink === null) {
            logger()->debug(sprintf('Unable to find patreon data for e-mail %s', $memberEmail));
            return false;
        }

        $user = $patreonUserLink->user;
        if ($user === null) {
            logger()->debug(sprintf('Unable to find user %s - user account may have deleted or e-mail changed', $memberEmail));
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
        } else {
            // Update the patreon benefits to their new status
            foreach ($newBenefits as $benefit) {
                if (!$user->hasPatreonBenefit($benefit)) {
                    PatreonUserBenefit::create([
                        'patreon_user_link_id' => $user->patreon_user_link_id,
                        'patreon_benefit_id'   => PatreonBenefit::ALL[$benefit],
                    ]);
                    logger()->debug(sprintf('Created new patreon benefit %s for user %s', $benefit, $user->email));
                }
            }

            // Check if any patreon benefits were removed, if so delete them
            $removedBenefits = $user->getPatreonBenefits()->diff($newBenefits);
            if ($removedBenefits->isNotEmpty()) {
                foreach ($removedBenefits as $removedBenefit) {
                    PatreonUserBenefit::where('patreon_user_link_id', $user->patreon_user_link_id)
                        ->where('patreon_benefit_id', PatreonBenefit::ALL[$removedBenefit])
                        ->delete();

                    logger()->debug(sprintf('Removed revoked patreon benefit %s for user %s', $removedBenefit, $user->email));
                }
            }
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
            return $this->cachedAdminUser;
        }

        // Admin is always user ID 1
        $adminUser = User::find(1);

        if ($adminUser === null) {
            logger()->error('Unable to fetch Admin user!');
            return null;
        }

        // Check if admin was setup correctly
        $adminUser->load(['patreonUserLink']);
        if ($adminUser->patreonUserLink === null) {
            logger()->error('Unable to refresh members - admin\'s Patreon data was not set! Login as admin and link the Patreon account');
            return null;
        }

        // Check if token is expired, if so refresh it
        if ($adminUser->patreonUserLink->isExpired()) {
            logger()->info('Admin tokens have expired!');
            $tokens = $patreonApiService->getAccessTokenFromRefreshToken($adminUser->patreonUserLink->refresh_token);

            if (isset($tokens['errors'])) {
                logger()->error('Unable to refresh expired access_token!');
                return null;
            } else {
                $adminUser->patreonUserLink->update([
                    'access_token'  => $tokens['access_token'],
                    'refresh_token' => $tokens['refresh_token'],
                    'expires_at'    => date('Y-m-d H:i:s', time() + $tokens['expires_in']),
                ]);

                logger()->info(sprintf('Updated expires_at to %s', date('Y-m-d H:i:s', time() + $tokens['expires_in'])));
            }
        }

        return $this->cachedAdminUser = $adminUser;
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
