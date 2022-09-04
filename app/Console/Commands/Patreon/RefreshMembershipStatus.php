<?php

namespace App\Console\Commands\Patreon;

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
     * @return int
     */
    public function handle(PatreonApiService $patreonApiService)
    {
        // Admin is always user ID 1
        $adminUser = User::find(1);

        if ($adminUser === null) {
            $this->error('Unable to fetch Admin user!');
            return -1;
        }

        // Check if admin was setup correctly
        $adminUser->load(['patreondata']);
        if ($adminUser->patreondata === null) {
            $this->error('Unable to refresh members - admin\'s Patreon data was not set! Login as admin and link the Patreon account');
            return -1;
        }

        // Check if token is expired, if so refresh it
        if ($adminUser->patreondata->isExpired()) {
            $tokens = $patreonApiService->getAccessTokenFromRefreshToken($adminUser->patreondata->refresh_token);

            if (isset($tokens['errors'])) {
                $this->error('Unable to refresh expired access_token!');
                return -1;
            } else {
                $adminUser->patreondata->update([
                    'access_token'  => $tokens['access_token'],
                    'refresh_token' => $tokens['refresh_token'],
                    'expires_in'    => date('Y-m-d H:i:s', time() + $tokens['expires_in']),
                ]);
            }
        }

        // Now that we have a valid token - perform the members request
        $membersResponse = $patreonApiService->getCampaignMembers($adminUser->patreondata->access_token);

        if (isset($membersResponse['errors'])) {
            $this->error('Error retrieving members!');
            return -1;
        }

        dd(count($membersResponse['data']));

        $tiersAndBenefitsResponse = $patreonApiService->getCampaignTiersAndBenefits($adminUser->patreondata->access_token);
        if (isset($tiersAndBenefitsResponse['errors'])) {
            $this->error('Error retrieving tiers!');
            return -1;
        }

        /** @var array{array{id: int, type: string}} $tiers */
        $benefits = collect($tiersAndBenefitsResponse['included'])->filter(function ($included) {
            return $included['type'] === 'benefit';
        });

        return 0;
    }
}
