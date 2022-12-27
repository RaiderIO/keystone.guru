<?php

namespace App\Console\Commands\Patreon;

use App\Service\Patreon\PatreonApiService;
use App\Service\Patreon\PatreonServiceInterface;
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
     * @param PatreonServiceInterface $patreonService
     * @return int
     */
    public function handle(PatreonApiService $patreonApiService, PatreonServiceInterface $patreonService): int
    {
        $campaignBenefits = $patreonService->loadCampaignBenefits($patreonApiService);
        $campaignTiers    = $patreonService->loadCampaignTiers($patreonApiService);
        $members          = $patreonService->loadCampaignMembers($patreonApiService);

        if ($campaignBenefits === null ||
            $campaignTiers === null ||
            $members === null) {
            $this->info(
                sprintf('Benefits, tiers or members are empty: %d, %d, %d', is_null($campaignBenefits), is_null($campaignTiers), is_null($members))
            );
            return -1;
        }

        // Update all found members in the database
        foreach ($members as $member) {
            $patreonService->applyPaidBenefitsForMember($campaignBenefits, $campaignTiers, $member);
        }

        $this->info(sprintf('Updated memberships of %s users', count($members)));

        return 0;
    }
}
