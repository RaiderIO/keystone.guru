<?php

namespace App\Console\Commands\Scheduler\Patreon;

use App\Console\Commands\Scheduler\SchedulerCommand;
use App\Service\Patreon\PatreonServiceInterface;
use Throwable;

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
            // Each of these logs its own reason for returning null - checking them separately keeps that reason
            // paired with a matching exit code instead of collapsing all three into one anonymous failure (#3748)
            $campaignBenefits = $patreonService->loadCampaignBenefits();
            if ($campaignBenefits === null) {
                $this->error('Unable to load the campaign benefits');

                return self::FAILURE;
            }

            $campaignTiers = $patreonService->loadCampaignTiers();
            if ($campaignTiers === null) {
                $this->error('Unable to load the campaign tiers');

                return self::FAILURE;
            }

            $members = $patreonService->loadCampaignMembers();
            if ($members === null) {
                $this->error('Unable to load the campaign members');

                return self::FAILURE;
            }

            // A single member we cannot apply the benefits for should not keep all remaining members from being
            // updated - but the command must still fail afterwards so that the failure doesn't go unnoticed
            $failedMemberIds = [];

            // Update all found members in the database
            foreach ($members as $member) {
                $memberId = isset($member['id']) && is_scalar($member['id']) ? (string)$member['id'] : 'unknown';

                try {
                    $patreonService->applyPaidBenefitsForMember($campaignBenefits, $campaignTiers, $member);
                } catch (Throwable $throwable) {
                    $failedMemberIds[] = $memberId;

                    $this->error(sprintf('Unable to apply the paid benefits of member %s: %s', $memberId, $throwable->getMessage()));
                    $this->reportThrowable($throwable, ['memberId' => $memberId]);
                }
            }

            $this->info(sprintf('Updated memberships of %s users', count($members) - count($failedMemberIds)));

            if ($failedMemberIds !== []) {
                $this->error(sprintf('Unable to update the memberships of %s member(s): %s', count($failedMemberIds), implode(', ', $failedMemberIds)));

                return self::FAILURE;
            }

            return self::SUCCESS;
        });
    }
}
