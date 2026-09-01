<?php

namespace App\Service\Patreon;

use App\Models\Patreon\PatreonBenefit;
use App\Models\Patreon\PatreonUserBenefit;
use App\Models\Patreon\PatreonUserLink;
use App\Models\User;
use App\Service\Patreon\Dtos\ApplyPaidBenefitsForMemberResult;
use App\Service\Patreon\Dtos\LinkToUserIdResult;
use App\Service\Patreon\Dtos\PatreonCampaignMembers;
use App\Service\Patreon\Dtos\PatreonMemberSyncPlan;
use App\Service\Patreon\Logging\PatreonServiceLoggingInterface;
use Exception;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class PatreonService implements PatreonServiceInterface
{
    private ?User $cachedAdminUser = null;

    public function __construct(
        private readonly PatreonApiServiceInterface     $patreonApiService,
        private readonly PatreonServiceLoggingInterface $log,
    ) {
    }

    /**
     * @return array<int, array{id: int, type: string, attributes: array{title: string}}>|null
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
            $tiersAndBenefitsResponse = $this->patreonApiService->getCampaignTiersAndBenefits($adminUser->patreonUserLink->access_token)->response;
            if (isset($tiersAndBenefitsResponse['errors'])) {
                $this->log->loadCampaignBenefitsRetrieveTiersErrors($tiersAndBenefitsResponse);

                return null;
            }

            if (!isset($tiersAndBenefitsResponse['included'])) {
                $this->log->loadCampaignBenefitsRetrieveTiersIncludedNotSet($tiersAndBenefitsResponse);

                return null;
            }

            /** @var array<int, mixed> $tiersAndBenefitsResponseIncluded */
            $tiersAndBenefitsResponseIncluded = $tiersAndBenefitsResponse['included'];

            return collect($tiersAndBenefitsResponseIncluded)->filter(static fn(
                $included,
            ) => $included['type'] === 'benefit')->toArray();
        } finally {
            $this->log->loadCampaignBenefitsEnd();
        }
    }

    /**
     * @return array<int, array<string, mixed>>|null
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
            $tiersAndBenefitsResponse = $this->patreonApiService->getCampaignTiersAndBenefits($adminUser->patreonUserLink->access_token)->response;
            if (isset($tiersAndBenefitsResponse['errors'])) {
                $this->log->loadCampaignTiersRetrieveTiersAndBenefitsErrors($tiersAndBenefitsResponse);

                return null;
            }

            if (!isset($tiersAndBenefitsResponse['included'])) {
                $this->log->loadCampaignTiersRetrieveMembersIncludedNotSet($tiersAndBenefitsResponse);

                return null;
            }

            /** @var array<int, mixed> $tiersAndBenefitsResponseIncluded */
            $tiersAndBenefitsResponseIncluded = $tiersAndBenefitsResponse['included'];

            return collect($tiersAndBenefitsResponseIncluded)->filter(static fn(
                $included,
            ) => $included['type'] === 'tier')->toArray();
        } finally {
            $this->log->loadCampaignTiersEnd();
        }
    }

    public function loadCampaignMembers(): ?PatreonCampaignMembers
    {
        if (($adminUser = $this->loadAdminUser()) === null) {
            $this->log->loadCampaignMembersAdminUserNull();

            return null;
        }

        try {
            $this->log->loadCampaignMembersStart();

            // Now that we have a valid token - perform the members request
            $pagedResponse   = $this->patreonApiService->getCampaignMembers($adminUser->patreonUserLink->access_token);
            $membersResponse = $pagedResponse->response;

            // A truncated fetch comes back memberless rather than as null, so the caller can still record
            // how far it got - a failure on page 5 is not the same as one on page 1
            if (isset($membersResponse['errors'])) {
                $this->log->loadCampaignTiersRetrieveMembersErrors($membersResponse);

                return new PatreonCampaignMembers([], $pagedResponse->pageCount, $pagedResponse->rowCount, truncated: true);
            }

            if (!isset($membersResponse['data'])) {
                $this->log->loadCampaignTiersRetrieveMembersDataNotSet($membersResponse);

                return new PatreonCampaignMembers([], $pagedResponse->pageCount, $pagedResponse->rowCount, truncated: true);
            }

            /** @var array<int, mixed> $membersResponseData */
            $membersResponseData = $membersResponse['data'];

            /** @var array<int, array<string, mixed>> $members */
            $members = collect($membersResponseData)->filter(static fn(
                $included,
            ) => $included['type'] === 'member')->values()->toArray();

            return new PatreonCampaignMembers($members, $pagedResponse->pageCount, $pagedResponse->rowCount, truncated: false);
        } finally {
            $this->log->loadCampaignMembersEnd();
        }
    }

    /**
     * @param array<int, array<string, mixed>> $campaignBenefits
     * @param array<int, array<string, mixed>> $campaignTiers
     * @param array<string, mixed>             $member
     */
    public function applyPaidBenefitsForMember(array $campaignBenefits, array $campaignTiers, array $member): ApplyPaidBenefitsForMemberResult
    {
        try {
            $this->log->applyPaidBenefitsForMemberStart($member['id']);

            $plan = $this->planPaidBenefitsForMember($campaignBenefits, $campaignTiers, $member);

            $patreonUserLink = $plan->patreonUserLink;

            // Stamp every link the campaign still lists, whatever the outcome was - this is the only record
            // that a sync actually reached this patron
            if ($patreonUserLink !== null) {
                PatreonUserLink::query()->whereKey($patreonUserLink->id)->update([
                    'last_seen_at'     => Carbon::now(),
                    'last_sync_result' => $plan->result->value,
                ]);
            }

            // Anything other than Applied means no trustworthy benefit set could be worked out, so nothing
            // is written - see the individual enum cases
            if ($plan->result !== ApplyPaidBenefitsForMemberResult::Applied || $patreonUserLink === null || $plan->manuallyGranted) {
                return $plan->result;
            }

            $user = $patreonUserLink->user;

            // If the user has no benefits (maybe user unsubbed or didn't pay up)
            if ($plan->resolvedBenefits === []) {
                // A row-level delete rather than a revoke of each benefit in the plan: it also clears rows
                // for benefits no longer in PatreonBenefit::ALL, which have no id to revoke by
                $patreonUserLink->patreonUserBenefits()->delete();
                $this->log->applyPaidBenefitsForMemberRemovedAllBenefits();
            } else {
                // Write against the link the member was matched to, not $user->patreon_user_link_id: the two
                // differ when the account's link pointer is stale, and then the pointer is the wrong one
                foreach ($plan->benefitsToAdd as $benefit) {
                    PatreonUserBenefit::create([
                        'patreon_user_link_id' => $patreonUserLink->id,
                        'patreon_benefit_id'   => PatreonBenefit::ALL[$benefit],
                    ]);
                    $this->log->applyPaidBenefitsAddedPatreonBenefit($benefit, $user->email);
                }

                foreach ($plan->benefitsToRevoke as $removedBenefit) {
                    // Benefits are keyed by title, so a benefit row in the database that we no longer know about
                    // (PatreonBenefit::ALL is the source of truth) has no id to delete by. Defensive only - every
                    // key currently seeded into patreon_benefits is present in PatreonBenefit::ALL
                    if (!isset(PatreonBenefit::ALL[$removedBenefit])) {
                        $this->log->applyPaidBenefitsForMemberUnknownPatreonBenefits([$removedBenefit], $user->email);

                        continue;
                    }

                    PatreonUserBenefit::where('patreon_user_link_id', $patreonUserLink->id)
                        ->where('patreon_benefit_id', PatreonBenefit::ALL[$removedBenefit])
                        ->delete();

                    $this->log->applyPaidBenefitsRevokedPatreonBenefit($removedBenefit, $user->email);
                }
            }
        } finally {
            $this->log->applyPaidBenefitsForMemberEnd();
        }

        return ApplyPaidBenefitsForMemberResult::Applied;
    }

    /**
     * Works out - without writing anything - what a sync would do to the account behind one campaign member.
     *
     * @param array<int, array<string, mixed>> $campaignBenefits
     * @param array<int, array<string, mixed>> $campaignTiers
     * @param array<string, mixed>             $member
     */
    public function planPaidBenefitsForMember(array $campaignBenefits, array $campaignTiers, array $member): PatreonMemberSyncPlan
    {
        $memberId = isset($member['id']) && is_scalar($member['id']) ? (string)$member['id'] : '';

        // The email attribute can be entirely absent for a member instead of present-but-null, so this can't
        // rely on the key always being set (#3767) - a missing key is handled the same as an empty value below
        $rawMemberEmail = $member['attributes']['email'] ?? null;
        $memberEmail    = is_string($rawMemberEmail) && $rawMemberEmail !== '' ? $rawMemberEmail : null;

        /** @var array<int, mixed> $entitledTierData */
        $entitledTierData = $member['relationships']['currently_entitled_tiers']['data'] ?? [];
        /** @var array<int, string> $entitledTierIds */
        $entitledTierIds = [];
        foreach ($entitledTierData as $currentlyEntitledTier) {
            if (isset($currentlyEntitledTier['id']) && is_scalar($currentlyEntitledTier['id'])) {
                $entitledTierIds[] = (string)$currentlyEntitledTier['id'];
            }
        }

        if ($memberEmail === null) {
            $this->log->applyPaidBenefitsForMemberEmptyMemberEmail();

            return $this->memberNotLinkedPlan($memberId, $memberEmail, $entitledTierIds);
        }

        /** @var PatreonUserLink|null $patreonUserLink */
        $patreonUserLink = PatreonUserLink::with(['user'])->where('email', $memberEmail)->first();

        if ($patreonUserLink === null) {
            $this->log->applyPaidBenefitsForMemberCannotFindPatreonData();

            return $this->memberNotLinkedPlan($memberId, $memberEmail, $entitledTierIds);
        }

        $user = $patreonUserLink->user;
        if ($user === null) { // @phpstan-ignore identical.alwaysFalse
            $this->log->applyPaidBenefitsForMemberCannotFindUserForPatreonUserLink();

            return $this->memberNotLinkedPlan($memberId, $memberEmail, $entitledTierIds);
        }

        /** @var array<int, string> $currentBenefits */
        $currentBenefits = $user->getPatreonBenefits()->values()->all();

        // Exception for users that were granted their membership status
        if ($patreonUserLink->refresh_token === PatreonUserLink::PERMANENT_TOKEN) {
            $this->log->applyPaidBenefitsForMemberUserManuallyAssignedAllBenefits();

            return new PatreonMemberSyncPlan(
                memberId: $memberId,
                memberEmail: $memberEmail,
                patreonUserLink: $patreonUserLink,
                entitledTierIds: $entitledTierIds,
                unresolvedTierIds: [],
                resolvedBenefits: [],
                unknownBenefits: [],
                currentBenefits: $currentBenefits,
                benefitsToAdd: [],
                benefitsToRevoke: [],
                manuallyGranted: true,
                result: ApplyPaidBenefitsForMemberResult::Applied,
            );
        }

        // We now know which user this is - work out the benefits of this user
        /** @var Collection<int, string> $resolvedBenefits */
        $resolvedBenefits = collect();
        /** @var array<int, string> $unresolvedTierIds */
        $unresolvedTierIds = [];
        foreach ($entitledTierIds as $entitledTierId) {
            // For all tiers this user is paying for - combine the benefits to one big array
            $tierBenefits = $this->getBenefitsByTierId($campaignTiers, $campaignBenefits, $entitledTierId);

            if ($tierBenefits === null) {
                $unresolvedTierIds[] = $entitledTierId;

                continue;
            }

            $resolvedBenefits = $resolvedBenefits->merge($tierBenefits);
        }
        $resolvedBenefits = $resolvedBenefits->unique()->values();

        // A tier the campaign response does not describe leaves an incomplete benefit set behind: with every
        // tier unresolved it computes to empty, which reads as "unsubscribed" below and revokes everything
        if ($unresolvedTierIds !== []) {
            $this->log->applyPaidBenefitsForMemberUnknownPatreonTiers($unresolvedTierIds, $user->email);

            return new PatreonMemberSyncPlan(
                memberId: $memberId,
                memberEmail: $memberEmail,
                patreonUserLink: $patreonUserLink,
                entitledTierIds: $entitledTierIds,
                unresolvedTierIds: $unresolvedTierIds,
                resolvedBenefits: $resolvedBenefits->all(),
                unknownBenefits: [],
                currentBenefits: $currentBenefits,
                benefitsToAdd: [],
                benefitsToRevoke: [],
                manuallyGranted: false,
                result: ApplyPaidBenefitsForMemberResult::UnknownTiers,
            );
        }

        // The benefit titles come straight from the campaign on patreon.com, so a renamed or newly added benefit
        // is not necessarily one we know about. Such a title used to fall through into an undefined array key
        // below, but skipping just that one title is worse than not syncing at all: the diff further down would
        // then revoke the benefit it was renamed from. Bail out entirely instead and log the unknown title so
        // it can be added to PatreonBenefit::ALL (#3748)
        $unknownBenefits = $resolvedBenefits->reject(static fn(string $benefit) => isset(PatreonBenefit::ALL[$benefit]))->values();
        if ($unknownBenefits->isNotEmpty()) {
            $this->log->applyPaidBenefitsForMemberUnknownPatreonBenefits($unknownBenefits->all(), $user->email);

            return new PatreonMemberSyncPlan(
                memberId: $memberId,
                memberEmail: $memberEmail,
                patreonUserLink: $patreonUserLink,
                entitledTierIds: $entitledTierIds,
                unresolvedTierIds: [],
                resolvedBenefits: $resolvedBenefits->all(),
                unknownBenefits: $unknownBenefits->all(),
                currentBenefits: $currentBenefits,
                benefitsToAdd: [],
                benefitsToRevoke: [],
                manuallyGranted: false,
                result: ApplyPaidBenefitsForMemberResult::UnknownBenefits,
            );
        }

        return new PatreonMemberSyncPlan(
            memberId: $memberId,
            memberEmail: $memberEmail,
            patreonUserLink: $patreonUserLink,
            entitledTierIds: $entitledTierIds,
            unresolvedTierIds: [],
            resolvedBenefits: $resolvedBenefits->all(),
            unknownBenefits: [],
            currentBenefits: $currentBenefits,
            benefitsToAdd: $resolvedBenefits->diff($currentBenefits)->values()->all(),
            benefitsToRevoke: collect($currentBenefits)->diff($resolvedBenefits)->values()->all(),
            manuallyGranted: false,
            result: ApplyPaidBenefitsForMemberResult::Applied,
        );
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
                    } elseif (!isset($identityResponse['included'])) {
                        $result = LinkToUserIdResult::InternalErrorOccurred;
                        $this->log->linkToUserAccountIdentityIncludedNotSet();
                    } else {
                        /** @var array<int, mixed> $identityResponseIncluded */
                        $identityResponseIncluded = $identityResponse['included'];
                        /** @var array<string, mixed>|null $member */
                        $member = collect($identityResponseIncluded)->filter(static fn(
                            array $included,
                        ) => $included['type'] === 'member')->first();

                        $patreonUserLinkAttributes['email'] = $identityResponse['data']['attributes']['email'];
                        $this->createPatreonUserLink($patreonUserLinkAttributes, $user);

                        // Now that the PatreonData object was created, apply the correct paid benefits to the account
                        $this->applyPaidBenefitsForMember(
                            $campaignBenefits,
                            $campaignTiers,
                            $member,
                        );
                    }
                }
            } else {
                $result = LinkToUserIdResult::PatreonSessionExpired;
                $this->log->linkToUserAccountSessionExpired();
            }
        } catch (Exception $e) {
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
                } elseif (!isset($tokens['access_token'])) {
                    $this->log->loadAdminUserAccessTokenNotSet($tokens);

                    return null;
                } elseif (!isset($tokens['refresh_token'])) {
                    $this->log->loadAdminUserRefreshTokenNotSet($tokens);

                    return null;
                } elseif (!isset($tokens['expires_in'])) {
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
     * The benefit titles one tier grants, or null when the campaign does not describe that tier at all.
     *
     * Null and an empty array mean different things to the caller: "we do not know what this tier grants"
     * versus "this tier grants nothing".
     *
     * Ids are compared as strings: the Patreon API is JSON:API, where resource ids are strings, so a
     * `===` against an int would never match and would land on the "tier not found" path.
     *
     * @param  array<int, array<string, mixed>> $campaignTiers
     * @param  array<int, array<string, mixed>> $campaignBenefits
     * @return array<int, string>|null
     */
    private function getBenefitsByTierId(array $campaignTiers, array $campaignBenefits, string $tierId): ?array
    {
        foreach ($campaignTiers as $tier) {
            if (!isset($tier['id']) || !is_scalar($tier['id']) || (string)$tier['id'] !== $tierId) {
                continue;
            }

            $result = [];

            // Found the tier, now match the benefits..
            /** @var array<int, array<string, mixed>> $tierBenefitData */
            $tierBenefitData = $tier['relationships']['benefits']['data'] ?? [];
            foreach ($tierBenefitData as $benefitData) {
                // Search the list of benefits for a match, and if found add the title to the result array
                foreach ($campaignBenefits as $campaignBenefit) {
                    if ((string)$campaignBenefit['id'] === (string)$benefitData['id']) {
                        $result[] = $campaignBenefit['attributes']['title'];
                        break;
                    }
                }
            }

            return $result;
        }

        return null;
    }

    /**
     * @param array<int, string> $entitledTierIds
     */
    private function memberNotLinkedPlan(string $memberId, ?string $memberEmail, array $entitledTierIds): PatreonMemberSyncPlan
    {
        return new PatreonMemberSyncPlan(
            memberId: $memberId,
            memberEmail: $memberEmail,
            patreonUserLink: null,
            entitledTierIds: $entitledTierIds,
            unresolvedTierIds: [],
            resolvedBenefits: [],
            unknownBenefits: [],
            currentBenefits: [],
            benefitsToAdd: [],
            benefitsToRevoke: [],
            manuallyGranted: false,
            result: ApplyPaidBenefitsForMemberResult::MemberNotLinked,
        );
    }

    /**
     * @param array<string, mixed> $attributes
     */
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
