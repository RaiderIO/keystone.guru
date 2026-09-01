<?php

namespace Tests\Feature\Console\Commands\Scheduler\Patreon;

use App\Console\Commands\Scheduler\Patreon\RefreshMembershipStatus;
use App\Models\Patreon\PatreonSyncRun;
use App\Service\Patreon\Dtos\ApplyPaidBenefitsForMemberResult;
use App\Service\Patreon\Dtos\PatreonCampaignMembers;
use App\Service\Patreon\PatreonServiceInterface;
use Illuminate\Console\Command;
use Illuminate\Contracts\Debug\ExceptionHandler;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use RuntimeException;
use Tests\TestCases\PublicTestCase;

#[Group('Console')]
#[Group('Scheduler')]
#[Group('Patreon')]
final class RefreshMembershipStatusTest extends PublicTestCase
{
    #[\Override]
    protected function tearDown(): void
    {
        try {
            // Every run of the command under test writes one of these
            PatreonSyncRun::query()->delete();
        } finally {
            parent::tearDown();
        }
    }

    #[Test]
    #[TestWith(['loadCampaignBenefits', 'Unable to load the campaign benefits'])]
    #[TestWith(['loadCampaignTiers', 'Unable to load the campaign tiers'])]
    #[TestWith(['loadCampaignMembers', 'Unable to load the campaign members'])]
    public function handle_givenAFailingLoad_returnsFailureAndNamesTheFailingLoad(string $failingMethod, string $expectedOutput): void
    {
        // Arrange
        $patreonService = $this->createMockPublic(PatreonServiceInterface::class);
        foreach (['loadCampaignBenefits', 'loadCampaignTiers', 'loadCampaignMembers'] as $method) {
            $patreonService->method($method)->willReturn(
                $method === $failingMethod ? null : ($method === 'loadCampaignMembers' ? new PatreonCampaignMembers([], 1, 0, truncated: false) : []),
            );
        }
        $patreonService->expects($this->never())->method('applyPaidBenefitsForMember');
        $this->app->instance(PatreonServiceInterface::class, $patreonService);

        // Act & Assert
        $this->artisan(RefreshMembershipStatus::class)
            ->expectsOutputToContain($expectedOutput)
            ->assertExitCode(Command::FAILURE);
    }

    #[Test]
    public function handle_givenAMemberThatThrows_reportsItAndKeepsUpdatingTheRemainingMembers(): void
    {
        // Arrange
        $exception = new RuntimeException('Undefined array key "some-new-benefit"');

        $exceptionHandler = $this->createMockPublic(ExceptionHandler::class);
        $exceptionHandler->expects($this->once())
            ->method('report')
            ->with($exception);
        $this->app->instance(ExceptionHandler::class, $exceptionHandler);

        $members = [
            ['id' => 'member-1', 'type' => 'member'],
            ['id' => 'member-2', 'type' => 'member'],
            ['id' => 'member-3', 'type' => 'member'],
        ];

        $patreonService = $this->createMockPublic(PatreonServiceInterface::class);
        $patreonService->method('loadCampaignBenefits')->willReturn([]);
        $patreonService->method('loadCampaignTiers')->willReturn([]);
        $patreonService->method('loadCampaignMembers')->willReturn(new PatreonCampaignMembers($members, 1, count($members), truncated: false));
        $patreonService->expects($this->exactly(count($members)))
            ->method('applyPaidBenefitsForMember')
            ->willReturnCallback(static function (array $campaignBenefits, array $campaignTiers, array $member) use ($exception): ApplyPaidBenefitsForMemberResult {
                if ($member['id'] === 'member-2') {
                    throw $exception;
                }

                return ApplyPaidBenefitsForMemberResult::Applied;
            });
        $this->app->instance(PatreonServiceInterface::class, $patreonService);

        // Act & Assert
        $this->artisan(RefreshMembershipStatus::class)
            ->expectsOutputToContain('Updated memberships of 2 users')
            ->expectsOutputToContain('member-2')
            ->assertExitCode(Command::FAILURE);
    }

    #[Test]
    public function handle_givenAllMembersApplySuccessfully_returnsSuccess(): void
    {
        // Arrange
        $members = [
            ['id' => 'member-1', 'type' => 'member'],
            ['id' => 'member-2', 'type' => 'member'],
        ];

        $patreonService = $this->createMockPublic(PatreonServiceInterface::class);
        $patreonService->method('loadCampaignBenefits')->willReturn([]);
        $patreonService->method('loadCampaignTiers')->willReturn([]);
        $patreonService->method('loadCampaignMembers')->willReturn(new PatreonCampaignMembers($members, 1, count($members), truncated: false));
        $patreonService->expects($this->exactly(count($members)))
            ->method('applyPaidBenefitsForMember')
            ->willReturn(ApplyPaidBenefitsForMemberResult::Applied);
        $this->app->instance(PatreonServiceInterface::class, $patreonService);

        // Act & Assert
        $this->artisan(RefreshMembershipStatus::class)
            ->expectsOutputToContain('Updated memberships of 2 users')
            ->assertExitCode(Command::SUCCESS);
    }

    #[Test]
    public function handle_givenAMemberWithUnknownBenefits_returnsFailureInsteadOfCountingItAsUpdated(): void
    {
        // Arrange
        $members = [
            ['id' => 'member-1', 'type' => 'member'],
            ['id' => 'member-2', 'type' => 'member'],
        ];

        $patreonService = $this->createMockPublic(PatreonServiceInterface::class);
        $patreonService->method('loadCampaignBenefits')->willReturn([]);
        $patreonService->method('loadCampaignTiers')->willReturn([]);
        $patreonService->method('loadCampaignMembers')->willReturn(new PatreonCampaignMembers($members, 1, count($members), truncated: false));
        $patreonService->method('applyPaidBenefitsForMember')
            ->willReturnCallback(static fn(array $campaignBenefits, array $campaignTiers, array $member) => $member['id'] === 'member-2'
                ? ApplyPaidBenefitsForMemberResult::UnknownBenefits
                : ApplyPaidBenefitsForMemberResult::Applied);
        $this->app->instance(PatreonServiceInterface::class, $patreonService);

        // Act & Assert
        $this->artisan(RefreshMembershipStatus::class)
            ->expectsOutputToContain('Updated memberships of 1 users')
            ->expectsOutputToContain('member-2')
            ->assertExitCode(Command::FAILURE);
    }

    #[Test]
    public function handle_givenAMemberWithoutALinkedAccount_countsItAsUpdatedAndReturnsSuccess(): void
    {
        // Arrange - by far the most common outcome, and not something to alert on
        $members = [
            ['id' => 'member-1', 'type' => 'member'],
            ['id' => 'member-2', 'type' => 'member'],
        ];

        $patreonService = $this->createMockPublic(PatreonServiceInterface::class);
        $patreonService->method('loadCampaignBenefits')->willReturn([]);
        $patreonService->method('loadCampaignTiers')->willReturn([]);
        $patreonService->method('loadCampaignMembers')->willReturn(new PatreonCampaignMembers($members, 1, count($members), truncated: false));
        $patreonService->method('applyPaidBenefitsForMember')
            ->willReturn(ApplyPaidBenefitsForMemberResult::MemberNotLinked);
        $this->app->instance(PatreonServiceInterface::class, $patreonService);

        // Act & Assert
        $this->artisan(RefreshMembershipStatus::class)
            ->assertExitCode(Command::SUCCESS);
    }

    #[Test]
    public function handle_givenMoreFailingMembersThanTheReportCap_reportsOnlyTheCappedAmount(): void
    {
        // Arrange - a systemic failure throws on every member, which must not write the same stack trace
        // to the log once per member
        $members = array_map(static fn(int $index) => ['id' => sprintf('member-%d', $index), 'type' => 'member'], range(1, 10));

        $exceptionHandler = $this->createMockPublic(ExceptionHandler::class);
        $exceptionHandler->expects($this->exactly(3))
            ->method('report');
        $this->app->instance(ExceptionHandler::class, $exceptionHandler);

        $patreonService = $this->createMockPublic(PatreonServiceInterface::class);
        $patreonService->method('loadCampaignBenefits')->willReturn([]);
        $patreonService->method('loadCampaignTiers')->willReturn([]);
        $patreonService->method('loadCampaignMembers')->willReturn(new PatreonCampaignMembers($members, 1, count($members), truncated: false));
        $patreonService->expects($this->exactly(count($members)))
            ->method('applyPaidBenefitsForMember')
            ->willThrowException(new RuntimeException('Everything is broken'));
        $this->app->instance(PatreonServiceInterface::class, $patreonService);

        // Act & Assert
        $this->artisan(RefreshMembershipStatus::class)
            ->expectsOutputToContain('Updated memberships of 0 users')
            ->assertExitCode(Command::FAILURE);
    }

    #[Test]
    public function handle_givenASuccessfulRun_recordsWhatItFetchedAndDecided(): void
    {
        // Arrange - the run row is the only trace of a sync that survives outside the container, so a
        // successful run that saw only half the campaign has to be visible in it afterwards (#4373)
        $members = [
            ['id' => 'member-1', 'type' => 'member'],
            ['id' => 'member-2', 'type' => 'member'],
            ['id' => 'member-3', 'type' => 'member'],
        ];

        $patreonService = $this->createMockPublic(PatreonServiceInterface::class);
        $patreonService->method('loadCampaignBenefits')->willReturn([]);
        $patreonService->method('loadCampaignTiers')->willReturn([]);
        $patreonService->method('loadCampaignMembers')->willReturn(new PatreonCampaignMembers($members, 4, count($members), truncated: false));
        $patreonService->method('applyPaidBenefitsForMember')
            ->willReturnCallback(static fn(array $campaignBenefits, array $campaignTiers, array $member) => $member['id'] === 'member-3'
                ? ApplyPaidBenefitsForMemberResult::MemberNotLinked
                : ApplyPaidBenefitsForMemberResult::Applied);
        $this->app->instance(PatreonServiceInterface::class, $patreonService);

        // Act
        $this->artisan(RefreshMembershipStatus::class)->assertExitCode(Command::SUCCESS);

        // Assert
        /** @var PatreonSyncRun $syncRun */
        $syncRun = PatreonSyncRun::query()->latest('id')->firstOrFail();
        $this->assertSame(4, $syncRun->pages_fetched);
        $this->assertSame(3, $syncRun->members_fetched);
        $this->assertSame(2, $syncRun->members_applied);
        $this->assertSame(1, $syncRun->members_not_linked);
        $this->assertSame(0, $syncRun->members_failed);
        $this->assertTrue($syncRun->successful);
        $this->assertFalse($syncRun->truncated);
        $this->assertNotNull($syncRun->finished_at);
    }

    #[Test]
    public function handle_givenATruncatedMemberFetch_recordsHowFarItGotAndAppliesNothing(): void
    {
        // Arrange - the fetch died on page 5 having collected 400 members. None of them may be applied
        // (every member it never saw would read as a cancellation), but the run row has to say where it
        // stopped - a failure after 400 members and one on the very first request are different problems
        $patreonService = $this->createMockPublic(PatreonServiceInterface::class);
        $patreonService->method('loadCampaignBenefits')->willReturn([]);
        $patreonService->method('loadCampaignTiers')->willReturn([]);
        $patreonService->method('loadCampaignMembers')->willReturn(new PatreonCampaignMembers([], 5, 400, truncated: true));
        $patreonService->expects($this->never())->method('applyPaidBenefitsForMember');
        $this->app->instance(PatreonServiceInterface::class, $patreonService);

        // Act
        $this->artisan(RefreshMembershipStatus::class)->assertExitCode(Command::FAILURE);

        // Assert
        /** @var PatreonSyncRun $syncRun */
        $syncRun = PatreonSyncRun::query()->latest('id')->firstOrFail();
        $this->assertTrue($syncRun->truncated);
        $this->assertFalse($syncRun->successful);
        $this->assertSame(5, $syncRun->pages_fetched);
        $this->assertSame(400, $syncRun->members_fetched);
        $this->assertSame('Unable to load the campaign members', $syncRun->failure_reason);
    }

    #[Test]
    public function handle_givenMembersThatCannotBeLoadedAtAll_recordsTheRunAsFailed(): void
    {
        // Arrange - null means the fetch could not even be attempted (no usable admin token)
        $patreonService = $this->createMockPublic(PatreonServiceInterface::class);
        $patreonService->method('loadCampaignBenefits')->willReturn([]);
        $patreonService->method('loadCampaignTiers')->willReturn([]);
        $patreonService->method('loadCampaignMembers')->willReturn(null);
        $this->app->instance(PatreonServiceInterface::class, $patreonService);

        // Act
        $this->artisan(RefreshMembershipStatus::class)->assertExitCode(Command::FAILURE);

        // Assert
        /** @var PatreonSyncRun $syncRun */
        $syncRun = PatreonSyncRun::query()->latest('id')->firstOrFail();
        $this->assertFalse($syncRun->truncated);
        $this->assertFalse($syncRun->successful);
        $this->assertSame(0, $syncRun->members_fetched);
    }

    #[Test]
    public function handle_givenAMemberWithUnknownTiers_returnsFailureAndRecordsIt(): void
    {
        // Arrange
        $members = [['id' => 'member-1', 'type' => 'member']];

        $patreonService = $this->createMockPublic(PatreonServiceInterface::class);
        $patreonService->method('loadCampaignBenefits')->willReturn([]);
        $patreonService->method('loadCampaignTiers')->willReturn([]);
        $patreonService->method('loadCampaignMembers')->willReturn(new PatreonCampaignMembers($members, 1, count($members), truncated: false));
        $patreonService->method('applyPaidBenefitsForMember')
            ->willReturn(ApplyPaidBenefitsForMemberResult::UnknownTiers);
        $this->app->instance(PatreonServiceInterface::class, $patreonService);

        // Act & Assert - it must not go quiet: the member kept their benefits, but nobody synced them
        $this->artisan(RefreshMembershipStatus::class)
            ->expectsOutputToContain('member-1')
            ->assertExitCode(Command::FAILURE);

        /** @var PatreonSyncRun $syncRun */
        $syncRun = PatreonSyncRun::query()->latest('id')->firstOrFail();
        $this->assertSame(1, $syncRun->members_unknown_tiers);
    }
}
