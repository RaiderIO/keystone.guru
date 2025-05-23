<?php

namespace App\Service\Patreon;

use App\Models\Patreon\PatreonBenefit;
use App\Models\Patreon\PatreonUserBenefit;
use App\Models\Patreon\PatreonUserLink;
use App\Models\User;
use App\Service\Patreon\Dtos\LinkToUserIdResult;
use App\Service\Patreon\Logging\PatreonServiceLoggingInterface;

class PatreonService implements PatreonServiceInterface
{
    private ?User $cachedAdminUser = null;

    public function __construct(
        private readonly PatreonApiServiceInterface     $patreonApiService,
        private readonly PatreonServiceLoggingInterface $log
    ) {
    }

    /**
     * @return array{array{id: int, type: string, attributes: array{title: string}}}|null
     */
    public function loadCampaignBenefits(): ?array
    {
        if (($adminUser = $this->loadAdminUser()) === null) {
            $this->log->loadCampaignBenefitsAdminUserNull();

            return null;
        }

        try {
            $this->log->loadCampaignBenefitsStart();

            // Fetch the tiers and benefits of a campaign
            $tiersAndBenefitsResponse = $this->patreonApiService->getCampaignTiersAndBenefits($adminUser->patreonUserLink->access_token);
            if (isset($tiersAndBenefitsResponse['errors'])) {
                $this->log->loadCampaignBenefitsRetrieveTiersErrors($tiersAndBenefitsResponse);

                return null;
            }

            if (!isset($tiersAndBenefitsResponse['included'])) {
                $this->log->loadCampaignBenefitsRetrieveTiersIncludedNotSet($tiersAndBenefitsResponse);

                return null;
            }

            return collect($tiersAndBenefitsResponse['included'])->filter(static fn($included) => $included['type'] === 'benefit')->toArray();
        } finally {
            $this->log->loadCampaignBenefitsEnd();
        }
    }

    /**
     * @return array{array{id: int, type: string, relationships: array}}|null
     */
    public function loadCampaignTiers(): ?array
    {
        if (($adminUser = $this->loadAdminUser()) === null) {
            $this->log->loadCampaignTiersAdminUserNull();

            return null;
        }

        try {
            $this->log->loadCampaignTiersStart();

            // Fetch the tiers and benefits of a campaign
            $tiersAndBenefitsResponse = $this->patreonApiService->getCampaignTiersAndBenefits($adminUser->patreonUserLink->access_token);
            if (isset($tiersAndBenefitsResponse['errors'])) {
                $this->log->loadCampaignTiersRetrieveTiersAndBenefitsErrors($tiersAndBenefitsResponse);

                return null;
            }

            if (!isset($tiersAndBenefitsResponse['included'])) {
                $this->log->loadCampaignTiersRetrieveMembersIncludedNotSet($tiersAndBenefitsResponse);

                return null;
            }

            return collect($tiersAndBenefitsResponse['included'])->filter(static fn($included) => $included['type'] === 'tier')->toArray();
        } finally {
            $this->log->loadCampaignTiersEnd();
        }
    }

    public function loadCampaignMembers(): ?array
    {
        if (($adminUser = $this->loadAdminUser()) === null) {
            $this->log->loadCampaignMembersAdminUserNull();

            return null;
        }

        try {
            $this->log->loadCampaignMembersStart();

            // Now that we have a valid token - perform the members request
            $membersResponse = $this->patreonApiService->getCampaignMembers($adminUser->patreonUserLink->access_token);
            if (isset($membersResponse['errors'])) {
                $this->log->loadCampaignTiersRetrieveMembersErrors($membersResponse);

                return null;
            }

            if (!isset($membersResponse['data'])) {
                $this->log->loadCampaignTiersRetrieveMembersDataNotSet($membersResponse);

                return null;
            }


            return collect($membersResponse['data'])->filter(static fn($included) => $included['type'] === 'member')->toArray();
        } finally {
            $this->log->loadCampaignMembersEnd();
        }
    }

    public function applyPaidBenefitsForMember(array $campaignBenefits, array $campaignTiers, array $member): bool
    {
        /** @var array{id: string, type: string, relationships: array, attributes: array{email: string}} $member */
        try {
            $this->log->applyPaidBenefitsForMemberStart($member['id']);

            $memberEmail = $member['attributes']['email'];

            if (empty($memberEmail)) {
                $this->log->applyPaidBenefitsForMemberEmptyMemberEmail();

                return false;
            }

            /** @var PatreonUserLink $patreonUserLink */
            $patreonUserLink = PatreonUserLink::with(['user'])->where('email', $memberEmail)->first();

            if ($patreonUserLink === null) {
                $this->log->applyPaidBenefitsForMemberCannotFindPatreonData();

                return false;
            }

            $user = $patreonUserLink->user;
            if ($user === null) {
                $this->log->applyPaidBenefitsForMemberCannotFindUserForPatreonUserLink();

                return false;
            }

            // Exception for users that were granted their membership status
            if ($patreonUserLink->refresh_token === PatreonUserLink::PERMANENT_TOKEN) {
                $this->log->applyPaidBenefitsForMemberUserManuallyAssignedAllBenefits();

                return true;
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
                $user->patreonUserLink->patreonUserBenefits()->delete();
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
            $this->log->applyPaidBenefitsForMemberEnd();
        }

        return true;
    }

    public function linkToUserAccount(User $user, string $code, string $redirectUri): LinkToUserIdResult
    {

        $result = LinkToUserIdResult::LinkSuccessful;
        try {
            $this->log->linkToUserAccountStart($user->id, $code, $redirectUri);

            $tokens = $this->patreonApiService->getAccessTokenFromCode($code, $redirectUri);
            $this->log->linkToUserAccountTokens($tokens);

            if (!isset($tokens['error'])) {
                // Save new tokens to database
                // Delete existing patreon data, if any
                $user->patreonUserLink?->delete();

                $patreonUserLinkAttributes = [
                    'user_id'       => $user->id,
                    'scope'         => $tokens['scope'],
                    'access_token'  => $tokens['access_token'],
                    'refresh_token' => $tokens['refresh_token'],
                    'version'       => $tokens['version'] ?? 2,
                    'expires_at'    => date('Y-m-d H:i:s', time() + $tokens['expires_in']),
                ];

                // Special case for the admin user - since the service needs this account to exist we need to just create
                // the PatreonData for this user and ignore the paid benefits (admins get everything, anyways)
                if ($user->id === 1) {
                    $this->log->linkToUserAccountAdminUser();
                    $patreonUserLinkAttributes['email'] = 'admin@app.com';
                    $this->createPatreonUserLink($patreonUserLinkAttributes, $user);
                } else {
                    // Fetch info we need to construct the PatreonData object/be able to link paid benefits
                    $campaignBenefits = $this->loadCampaignBenefits();
                    $campaignTiers    = $this->loadCampaignTiers();

                    $identityResponse = $this->patreonApiService->getIdentity($tokens['access_token']);
                    $this->log->linkToUserAccountIdentityResponse($identityResponse);
                    if (isset($identityResponse['errors'])) {
                        $result = LinkToUserIdResult::PatreonErrorOccurred;
                        // Not sure if this is an array - make it so
                        if (!is_array($identityResponse['errors'])) {
                            $identityResponse['errors'] = [$identityResponse['errors']];
                        }
                        $this->log->linkToUserAccountIdentityError($identityResponse['errors']);
                    } else if (!isset($identityResponse['included'])) {
                        $result = LinkToUserIdResult::InternalErrorOccurred;
                        $this->log->linkToUserAccountIdentityIncludedNotSet();
                    } else {
                        $member = collect($identityResponse['included'])->filter(static fn(array $included) => $included['type'] === 'member')->first();

                        $patreonUserLinkAttributes['email'] = $identityResponse['data']['attributes']['email'];
                        $this->createPatreonUserLink($patreonUserLinkAttributes, $user);

                        // Now that the PatreonData object was created, apply the correct paid benefits to the account
                        $this->applyPaidBenefitsForMember(
                            $campaignBenefits,
                            $campaignTiers,
                            $member
                        );
                    }
                }
            } else {
                $result = LinkToUserIdResult::PatreonSessionExpired;
                $this->log->linkToUserAccountSessionExpired();
            }
        } catch (\Exception $e) {
            $result = LinkToUserIdResult::InternalErrorOccurred;

            $this->log->linkToUserAccountException($e);
        } finally {
            $this->log->linkToUserAccountEnd($result);
        }

        return $result;
    }


    private function loadAdminUser(): ?User
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
                $tokens = $this->patreonApiService->getAccessTokenFromRefreshToken($adminUser->patreonUserLink->refresh_token);

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

    private function createPatreonUserLink(array $attributes, User $user): PatreonUserLink
    {
        $existingPatreonUserLink = PatreonUserLink::where('email', $attributes['email'])->first();

        // If the link already exists, remove it entirely. Can't couple the same Patreon account to 2 Keystone.guru accounts
        if ($existingPatreonUserLink !== null) {
            $existingPatreonUserLink->user()->update(['patreon_user_link_id' => null]);

            $existingPatreonUserLink->delete();
        }

        // Create a new PatreonData object and assign it to the user
        $patreonUserLink = PatreonUserLink::create($attributes);
        $user->update([
            'patreon_user_link_id' => $patreonUserLink->id,
        ]);
        $user->patreonUserLink = $patreonUserLink;

        $this->log->createPatreonUserLinkSuccessful($user->id, $patreonUserLink->id);

        return $patreonUserLink;
    }
}
