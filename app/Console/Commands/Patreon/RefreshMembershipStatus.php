<?php

namespace App\Console\Commands\Patreon;

use App\Models\PaidTier;
use App\Models\PatreonData;
use App\Models\PatreonTier;
use App\Service\Patreon\PatreonApiService;
use App\User;
use Illuminate\Console\Command;

class RefreshMembershipStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'patreon:refreshmembers';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetches all membership status from the Patreon API and re-applies their pledge status';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @param PatreonApiService $patreonApiService
     * @return int
     */
    public function handle(PatreonApiService $patreonApiService): int
    {
        if (($adminUser = $this->loadAdminUser($patreonApiService)) === null) {
            return -1;
        }

        $campaignBenefits = $campaignTiers = $members = [];
        if (!$this->loadPatreonData($patreonApiService, $adminUser, $campaignBenefits, $campaignTiers, $members)) {
            return -1;
        }

        $this->refreshMembershipStatus($campaignBenefits, $campaignTiers, $members);

        return 0;
    }

    /**
     * @param PatreonApiService $patreonApiService
     * @return void
     */
    private function loadAdminUser(PatreonApiService $patreonApiService): ?User
    {
        // Admin is always user ID 1
        $adminUser = User::find(1);

        if ($adminUser === null) {
            $this->error('Unable to fetch Admin user!');
            return null;
        }

        // Check if admin was setup correctly
        $adminUser->load(['patreondata']);
        if ($adminUser->patreondata === null) {
            $this->error('Unable to refresh members - admin\'s Patreon data was not set! Login as admin and link the Patreon account');
            return null;
        }

        // Check if token is expired, if so refresh it
        if ($adminUser->patreondata->isExpired()) {
            $this->info('Admin tokens have expired!');
            $tokens = $patreonApiService->getAccessTokenFromRefreshToken($adminUser->patreondata->refresh_token);

            if (isset($tokens['errors'])) {
                $this->error('Unable to refresh expired access_token!');
                return null;
            } else {
                $adminUser->patreondata->update([
                    'access_token'  => $tokens['access_token'],
                    'refresh_token' => $tokens['refresh_token'],
                    'expires_at'    => date('Y-m-d H:i:s', time() + $tokens['expires_in']),
                ]);

                $this->info(sprintf('Updated expires_at to %s', date('Y-m-d H:i:s', time() + $tokens['expires_in'])));
            }
        }

        return $adminUser;
    }

    /**
     * @param PatreonApiService $patreonApiService
     * @param User $adminUser
     * @param array $campaignBenefits
     * @param array $campaignTiers
     * @param array $members
     * @return bool
     */
    private function loadPatreonData(PatreonApiService $patreonApiService, User $adminUser, array &$campaignBenefits, array &$campaignTiers, array &$members): bool
    {
        // Now that we have a valid token - perform the members request
        $membersResponse = $patreonApiService->getCampaignMembers($adminUser->patreondata->access_token);
        if (isset($membersResponse['errors'])) {
            $this->error('Error retrieving members!');
            $this->warn(json_encode($membersResponse));
            return false;
        }

        // Fetch the tiers and benefits of a campaign
        $tiersAndBenefitsResponse = $patreonApiService->getCampaignTiersAndBenefits($adminUser->patreondata->access_token);
        if (isset($tiersAndBenefitsResponse['errors'])) {
            $this->error('Error retrieving tiers!');
            $this->warn(json_encode($tiersAndBenefitsResponse));
            return false;
        }

        /** @var $campaignBenefits array{array{id: int, type: string, attributes: array{title: string}}} */
        $campaignBenefits = collect($tiersAndBenefitsResponse['included'])->filter(function ($included) {
            return $included['type'] === 'benefit';
        })->toArray();

        /** @var $campaignTiers array{array{id: int, type: string, relationships: array}} */
        $campaignTiers = collect($tiersAndBenefitsResponse['included'])->filter(function ($included) {
            return $included['type'] === 'tier';
        })->toArray();


        $members = collect($membersResponse['data'])->filter(function ($included) {
            return $included['type'] === 'member';
        })->toArray();

        return true;
    }

    /**
     * @param array $campaignBenefits
     * @param array $campaignTiers
     * @param array $members
     * @return void
     */
    private function refreshMembershipStatus(array $campaignBenefits, array $campaignTiers, array $members): void
    {
        foreach ($members as $member) {
            /** @var array{id: string, type: string, relationships: array, attributes: array{email: string}} $member */
            $memberEmail = $member['attributes']['email'];

            if (empty($memberEmail)) {
                $this->warn('Cannot find patreon data for empty e-mail');
                continue;
            }

            /** @var PatreonData $patreonData */
            $patreonData = PatreonData::with(['user'])->where('email', $memberEmail)->first();

            if ($patreonData === null) {
                $this->warn(sprintf('Unable to find patreon data for e-mail %s', $memberEmail));
                continue;
            }

            $user = $patreonData->user;
            if ($user === null) {
                $this->warn(sprintf('Unable to find user %s - user account may have deleted or e-mail changed', $memberEmail));
                continue;
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
                $user->patreondata->tiers()->delete();
            } else {
                // Update the paid tiers to their new status
                foreach ($newBenefits as $benefit) {
                    if (!$user->hasPaidTier($benefit)) {
                        PatreonTier::create([
                            'patreon_data_id' => $user->patreon_data_id,
                            'paid_tier_id'    => PaidTier::ALL[$benefit],
                        ]);
                        $this->info(sprintf('Created new paid tier %s for user %s', $benefit, $user->email));
                    }
                }

                // Check if any paid tiers were removed, if so delete them
                $removedBenefits = $user->getPaidTiers()->diff($newBenefits);
                if ($removedBenefits->isNotEmpty()) {
                    foreach ($removedBenefits as $removedBenefit) {
                        PatreonTier::where('patreon_data_id', $user->patreon_data_id)
                            ->where('paid_tier_id', PaidTier::ALL[$removedBenefit])
                            ->delete();

                        $this->info(sprintf('Removed revoked paid tier %s for user %s', $removedBenefit, $user->email));
                    }
                }
            }
        }

        $this->info(sprintf('Updated memberships of %s users', count($members)));
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
