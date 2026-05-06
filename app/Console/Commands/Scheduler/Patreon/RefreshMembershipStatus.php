<?php

namespace App\Console\Commands\Scheduler\Patreon;

use App\Console\Commands\Scheduler\SchedulerCommand;
use App\Service\Patreon\PatreonServiceInterface;

class RefreshMembershipStatus extends SchedulerCommand
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
     * Execute the console command.
     */
    public function handle(PatreonServiceInterface $patreonService): int
    {
        return $this->trackTime(function () use ($patreonService) {
            $campaignBenefits = $patreonService->loadCampaignBenefits();
            $campaignTiers    = $patreonService->loadCampaignTiers();
            $members          = $patreonService->loadCampaignMembers();

            if ($campaignBenefits === null ||
                $campaignTiers === null ||
                $members === null) {
                return -1;
            }

            // Update all found members in the database
            foreach ($members as $member) {
                $patreonService->applyPaidBenefitsForMember($campaignBenefits, $campaignTiers, $member);
            }

            $this->info(sprintf('Updated memberships of %s users', count($members)));

            return 0;
        });
    }
}
