<?php

namespace Tests\Feature\Console\Commands\Scheduler\Patreon;

use App\Console\Commands\Scheduler\Patreon\RefreshMembershipStatus;
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
            $patreonService->method($method)->willReturn($method === $failingMethod ? null : []);
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
        $patreonService->method('loadCampaignMembers')->willReturn($members);
        $patreonService->expects($this->exactly(count($members)))
            ->method('applyPaidBenefitsForMember')
            ->willReturnCallback(static function (array $campaignBenefits, array $campaignTiers, array $member) use ($exception): bool {
                if ($member['id'] === 'member-2') {
                    throw $exception;
                }

                return true;
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
        $patreonService->method('loadCampaignMembers')->willReturn($members);
        $patreonService->expects($this->exactly(count($members)))
            ->method('applyPaidBenefitsForMember')
            ->willReturn(true);
        $this->app->instance(PatreonServiceInterface::class, $patreonService);

        // Act & Assert
        $this->artisan(RefreshMembershipStatus::class)
            ->expectsOutputToContain('Updated memberships of 2 users')
            ->assertExitCode(Command::SUCCESS);
    }
}
