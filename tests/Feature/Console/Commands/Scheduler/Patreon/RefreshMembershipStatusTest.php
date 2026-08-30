<?php

namespace Tests\Feature\Console\Commands\Scheduler\Patreon;

use App\Console\Commands\Scheduler\Patreon\RefreshMembershipStatus;
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
                $method === $failingMethod ? null : ($method === 'loadCampaignMembers' ? new PatreonCampaignMembers([], 1) : []),
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
        $patreonService->method('loadCampaignMembers')->willReturn(new PatreonCampaignMembers($members, 1));
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
        $patreonService->method('loadCampaignMembers')->willReturn(new PatreonCampaignMembers($members, 1));
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
        $patreonService->method('loadCampaignMembers')->willReturn(new PatreonCampaignMembers($members, 1));
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
        $patreonService->method('loadCampaignMembers')->willReturn(new PatreonCampaignMembers($members, 1));
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
        $patreonService->method('loadCampaignMembers')->willReturn(new PatreonCampaignMembers($members, 1));
        $patreonService->expects($this->exactly(count($members)))
            ->method('applyPaidBenefitsForMember')
            ->willThrowException(new RuntimeException('Everything is broken'));
        $this->app->instance(PatreonServiceInterface::class, $patreonService);

        // Act & Assert
        $this->artisan(RefreshMembershipStatus::class)
            ->expectsOutputToContain('Updated memberships of 0 users')
            ->assertExitCode(Command::FAILURE);
    }
}
