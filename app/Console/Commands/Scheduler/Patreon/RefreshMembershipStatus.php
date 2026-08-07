<?php

namespace App\Console\Commands\Scheduler\Patreon;

use App\Console\Commands\Scheduler\SchedulerCommand;
use App\Service\Patreon\Dtos\ApplyPaidBenefitsForMemberResult;
use App\Service\Patreon\PatreonServiceInterface;
use Throwable;

class RefreshMembershipStatus extends SchedulerCommand
{
    /** @var int How many individual member failures are reported through the exception handler before we stop */
    private const int MAX_REPORTED_MEMBER_FAILURES = 3;

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
                    $result = $patreonService->applyPaidBenefitsForMember($campaignBenefits, $campaignTiers, $member);

                    // A member without a linked Keystone.guru account is the common case and not a failure, but a
                    // member whose benefits we cannot compute is - silently counting it as updated would let the
                    // hourly alert go quiet while benefits stop being applied (#3748)
                    if ($result === ApplyPaidBenefitsForMemberResult::UnknownBenefits) {
                        $failedMemberIds[] = $memberId;

                        $this->error(sprintf(
                            'Member %s is entitled to a benefit that is missing from PatreonBenefit::ALL - see the logged benefit titles',
                            $memberId,
                        ));
                    }
                } catch (Throwable $throwable) {
                    $this->error(sprintf('Unable to apply the paid benefits of member %s: %s', $memberId, $throwable->getMessage()));

                    // A systemic failure fails on every single member - reporting each one would write the same
                    // stack trace to the log hundreds of times over. The first few are enough to diagnose it, and
                    // the summary below still names every member that failed
                    if (count($failedMemberIds) < self::MAX_REPORTED_MEMBER_FAILURES) {
                        $this->reportThrowable($throwable, ['memberId' => $memberId]);
                    }

                    $failedMemberIds[] = $memberId;
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
