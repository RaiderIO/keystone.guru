<?php

namespace App\Console\Commands\Scheduler\Patreon;

use App\Console\Commands\Scheduler\SchedulerCommand;
use App\Models\Patreon\PatreonSyncRun;
use App\Repositories\Interfaces\Patreon\PatreonSyncRunRepositoryInterface;
use App\Service\Patreon\Dtos\ApplyPaidBenefitsForMemberResult;
use App\Service\Patreon\PatreonServiceInterface;
use Illuminate\Support\Carbon;
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
    public function handle(
        PatreonServiceInterface           $patreonService,
        PatreonSyncRunRepositoryInterface $patreonSyncRunRepository,
    ): int {
        return $this->trackTime(function () use ($patreonService, $patreonSyncRunRepository) {
            // Every exit below records how far the run got before it stopped - the command's log lines
            // never leave the container, so the row is the only trace of its outcome
            $syncRun  = $patreonSyncRunRepository->create(['started_at' => Carbon::now()]);
            $finished = false;

            try {
                // Each of these logs its own reason for returning null - checking them separately keeps that reason
                // paired with a matching exit code instead of collapsing all three into one anonymous failure (#3748)
                $campaignBenefits = $patreonService->loadCampaignBenefits();
                if ($campaignBenefits === null) {
                    $this->error('Unable to load the campaign benefits');

                    $finished = true;

                    return $this->finishRun($syncRun, false, 'Unable to load the campaign benefits');
                }

                $campaignTiers = $patreonService->loadCampaignTiers();
                if ($campaignTiers === null) {
                    $this->error('Unable to load the campaign tiers');

                    $finished = true;

                    return $this->finishRun($syncRun, false, 'Unable to load the campaign tiers');
                }

                $campaignMembers = $patreonService->loadCampaignMembers();
                if ($campaignMembers === null) {
                    $this->error('Unable to load the campaign members');

                    $finished = true;

                    return $this->finishRun($syncRun, false, 'Unable to load the campaign members');
                }

                // Recorded before anything decides what to do with it, so a truncated run says where it
                // stopped rather than looking like a total failure
                $syncRun->update([
                    'pages_fetched'   => $campaignMembers->pageCount,
                    'members_fetched' => $campaignMembers->rowCount,
                ]);

                // A partial member list is never processed: every member it does not contain would read as
                // a member who cancelled, and have their benefits revoked
                if ($campaignMembers->truncated) {
                    $this->error(sprintf(
                        'The campaign member list came back truncated after %d page(s) and %d member(s) - not applying anything',
                        $campaignMembers->pageCount,
                        $campaignMembers->rowCount,
                    ));

                    $finished = true;

                    return $this->finishRun($syncRun, false, 'Unable to load the campaign members', truncated: true);
                }

                $members = $campaignMembers->members;

                // A single member we cannot apply the benefits for should not keep all remaining members from being
                // updated - but the command must still fail afterwards so that the failure doesn't go unnoticed
                $failedMemberIds = [];
                /** @var array<int, int> $resultCounts Keyed by ApplyPaidBenefitsForMemberResult value */
                $resultCounts = [];

                // Update all found members in the database
                foreach ($members as $member) {
                    $memberId = isset($member['id']) && is_scalar($member['id']) ? (string)$member['id'] : 'unknown';

                    try {
                        $result = $patreonService->applyPaidBenefitsForMember($campaignBenefits, $campaignTiers, $member);

                        $resultCounts[$result->value] = ($resultCounts[$result->value] ?? 0) + 1;

                        // A member without a linked Keystone.guru account is the common case and not a failure, but a
                        // member whose benefits we cannot compute is - silently counting it as updated would let the
                        // hourly alert go quiet while benefits stop being applied (#3748)
                        if ($result === ApplyPaidBenefitsForMemberResult::UnknownBenefits) {
                            $failedMemberIds[] = $memberId;

                            $this->error(sprintf(
                                'Member %s is entitled to a benefit that is missing from PatreonBenefit::ALL - see the logged benefit titles',
                                $memberId,
                            ));
                        } elseif ($result === ApplyPaidBenefitsForMemberResult::UnknownTiers) {
                            $failedMemberIds[] = $memberId;

                            $this->error(sprintf(
                                'Member %s is entitled to a tier the campaign does not describe - their benefits were left untouched, see the logged tier ids',
                                $memberId,
                            ));
                        }
                    } catch (Throwable $throwable) {
                        $this->error(sprintf('Unable to apply the paid benefits of member %s: %s', $memberId, $throwable->getMessage()));

                        // A systemic failure fails on every member - reporting each one would write the same stack
                        // trace hundreds of times. The summary below still names every member that failed
                        if (count($failedMemberIds) < self::MAX_REPORTED_MEMBER_FAILURES) {
                            $this->reportThrowable($throwable, ['memberId' => $memberId]);
                        }

                        $failedMemberIds[] = $memberId;
                    }
                }

                $this->info(sprintf('Updated memberships of %s users', count($members) - count($failedMemberIds)));

                $syncRun->update([
                    'members_applied'          => $resultCounts[ApplyPaidBenefitsForMemberResult::Applied->value] ?? 0,
                    'members_not_linked'       => $resultCounts[ApplyPaidBenefitsForMemberResult::MemberNotLinked->value] ?? 0,
                    'members_unknown_benefits' => $resultCounts[ApplyPaidBenefitsForMemberResult::UnknownBenefits->value] ?? 0,
                    'members_unknown_tiers'    => $resultCounts[ApplyPaidBenefitsForMemberResult::UnknownTiers->value] ?? 0,
                    'members_failed'           => count($failedMemberIds),
                ]);

                $finished = true;

                if ($failedMemberIds !== []) {
                    $failureReason = sprintf('Unable to update the memberships of %s member(s): %s', count($failedMemberIds), implode(', ', $failedMemberIds));
                    $this->error($failureReason);

                    return $this->finishRun($syncRun, false, $failureReason);
                }

                return $this->finishRun($syncRun, true);
            } finally {
                // An exception thrown above skips every explicit finishRun() call, leaving the run's row open
                if (!$finished) {
                    $this->finishRun($syncRun, false, 'The run was interrupted by an unexpected error');
                }
            }
        });
    }

    /**
     * Closes off the run's row and returns the exit code that goes with it.
     */
    private function finishRun(PatreonSyncRun $syncRun, bool $successful, ?string $failureReason = null, bool $truncated = false): int
    {
        $syncRun->update([
            'finished_at' => Carbon::now(),
            'successful'  => $successful,
            'truncated'   => $truncated,
            // The column is a string - a long list of failing member ids would not fit
            'failure_reason' => $failureReason === null ? null : mb_substr($failureReason, 0, 255),
        ]);

        return $successful ? self::SUCCESS : self::FAILURE;
    }
}
