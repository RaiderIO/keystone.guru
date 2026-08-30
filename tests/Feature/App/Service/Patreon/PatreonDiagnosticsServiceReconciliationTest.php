<?php

namespace Tests\Feature\App\Service\Patreon;

use App\Models\Laratrust\Role;
use App\Models\Patreon\PatreonBenefit;
use App\Models\Patreon\PatreonUserBenefit;
use App\Models\Patreon\PatreonUserLink;
use App\Models\User;
use App\Service\Patreon\Dtos\Diagnostics\PatreonBenefitHolderDiagnostics;
use App\Service\Patreon\Dtos\Diagnostics\PatreonBenefitReconciliation;
use App\Service\Patreon\Dtos\PatreonCampaignMembers;
use App\Service\Patreon\Dtos\PatreonOverEntitlementReason;
use App\Service\Patreon\Logging\PatreonServiceLoggingInterface;
use App\Service\Patreon\PatreonApiServiceInterface;
use App\Service\Patreon\PatreonDiagnosticsServiceInterface;
use App\Service\Patreon\PatreonService;
use App\Service\Patreon\PatreonServiceInterface;
use Override;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

/**
 * The cross-reference that finds accounts holding more Patreon benefits than the campaign grants them
 * (#4386).
 *
 * Only the three campaign loaders are mocked - `planPaidBenefitsForMember()` runs for real, because the
 * whole point of the report is that it matches members exactly the way the hourly sync does. A second
 * implementation of that matching would agree with itself and still be wrong.
 */
#[Group('Patreon')]
#[Group('PatreonDiagnosticsService')]
final class PatreonDiagnosticsServiceReconciliationTest extends PublicTestCase
{
    private const array CAMPAIGN_BENEFITS = [
        ['id' => '367345', 'type' => 'benefit', 'attributes' => ['title' => PatreonBenefit::AD_FREE]],
        ['id' => '367914', 'type' => 'benefit', 'attributes' => ['title' => PatreonBenefit::UNLISTED_ROUTES]],
    ];

    /** Two tiers, so a member can be moved to the cheaper one to model a downgrade. */
    private const array CAMPAIGN_TIERS = [
        [
            'id'            => '2971575',
            'type'          => 'tier',
            'attributes'    => ['title' => 'Supporter of Keystone.guru'],
            'relationships' => ['benefits' => ['data' => [
                ['id' => '367345', 'type' => 'benefit'],
                ['id' => '367914', 'type' => 'benefit'],
            ]]],
        ],
        [
            'id'            => '2971576',
            'type'          => 'tier',
            'attributes'    => ['title' => 'Cheaper tier'],
            'relationships' => ['benefits' => ['data' => [
                ['id' => '367345', 'type' => 'benefit'],
            ]]],
        ],
    ];

    /** @var array<int, int> */
    private array $createdUserIds = [];

    /** @var array<int, int> */
    private array $createdLinkIds = [];

    #[Override]
    protected function tearDown(): void
    {
        try {
            if ($this->createdLinkIds !== []) {
                PatreonUserBenefit::query()->whereIn('patreon_user_link_id', $this->createdLinkIds)->delete();
                PatreonUserLink::query()->whereIn('id', $this->createdLinkIds)->delete();
            }

            if ($this->createdUserIds !== []) {
                User::query()->whereIn('id', $this->createdUserIds)->delete();
            }
        } finally {
            parent::tearDown();
        }
    }

    #[Test]
    public function getBenefitReconciliation_givenAnAccountWhoseBenefitsMatchItsTier_reportsNothingForIt(): void
    {
        // Arrange - the case that dominates production. Without it, a bug that dumps every matched link
        // into the unmatched bucket would still pass every other test in this file
        $link = $this->createLinkedUser();
        $this->grantBenefits($link, [PatreonBenefit::AD_FREE, PatreonBenefit::UNLISTED_ROUTES]);

        // Act
        $reconciliation = $this->reconcile([$this->member($link->email, ['2971575'])]);

        // Assert
        $this->assertNull($this->findHolder($reconciliation, $link->id));
        $this->assertSame(0, $reconciliation->downgradedCount);
        $this->assertFalse($reconciliation->needsAttention());
    }

    #[Test]
    public function getBenefitReconciliation_givenAnAccountTheCampaignNoLongerLists_reportsItAsUnmatched(): void
    {
        // Arrange - a patron who cancelled and dropped off the campaign, or deleted their Patreon
        // account. Nothing in the hourly sync will ever revoke these, which is why the report exists
        $link = $this->createLinkedUser();
        $this->grantBenefits($link, [PatreonBenefit::AD_FREE]);

        // Act - a campaign that lists somebody else entirely
        $reconciliation = $this->reconcile([$this->member('someone-else@example.test', ['2971575'])]);

        // Assert
        $holder = $this->findHolder($reconciliation, $link->id);
        $this->assertNotNull($holder);
        $this->assertSame(PatreonOverEntitlementReason::NoCampaignMember, $holder->reason);
        $this->assertSame([PatreonBenefit::AD_FREE], $holder->storedBenefits);
        $this->assertNull($holder->emailDriftCandidate);
        $this->assertContains($holder, $reconciliation->unmatchedHolders);
        $this->assertTrue($reconciliation->needsAttention());
    }

    #[Test]
    public function getBenefitReconciliation_givenACampaignMemberCarryingTheAccountEmail_reportsEmailDrift(): void
    {
        // Arrange - the patron changed their email on Patreon after linking. The sync matches links by
        // email, so it has read as "never linked" ever since and has never revoked anything
        $link = $this->createLinkedUser();
        $this->grantBenefits($link, [PatreonBenefit::AD_FREE]);
        $accountEmail = User::query()->whereKey($link->user_id)->value('email');

        // Act - the campaign knows them under their account email, the link still stores the old one
        $reconciliation = $this->reconcile([$this->member($accountEmail, ['2971575'])]);

        // Assert
        $holder = $this->findHolder($reconciliation, $link->id);
        $this->assertNotNull($holder);
        $this->assertSame(PatreonOverEntitlementReason::EmailDrift, $holder->reason);
        $this->assertNotNull($holder->emailDriftCandidate);
    }

    #[Test]
    public function getBenefitReconciliation_givenALinkWhoseUserWasDeleted_stillReportsIt(): void
    {
        // Arrange - User::deleting only removes one link via HasOne::first(), so a duplicate row outlives
        // its user still holding benefit grants. The admin filter must not silently swallow those
        $link = $this->createLinkedUser();
        $this->grantBenefits($link, [PatreonBenefit::AD_FREE]);
        User::query()->whereKey($link->user_id)->delete();

        // Act
        $reconciliation = $this->reconcile([]);

        // Assert
        $holder = $this->findHolder($reconciliation, $link->id);
        $this->assertNotNull($holder);
        $this->assertSame(PatreonOverEntitlementReason::NoCampaignMember, $holder->reason);
        $this->assertNull($holder->userId);
    }

    #[Test]
    public function getBenefitReconciliation_givenAManuallyGrantedLink_excludesItFromTheReport(): void
    {
        // Arrange - benefits handed out through the admin pages are not something the campaign justifies
        // and never will be, so they are noise in every bucket
        $link = $this->createLinkedUser(manuallyGranted: true);
        $this->grantBenefits($link, [PatreonBenefit::AD_FREE, PatreonBenefit::UNLISTED_ROUTES]);

        // Act
        $reconciliation = $this->reconcile([]);

        // Assert
        $this->assertNull($this->findHolder($reconciliation, $link->id));
    }

    #[Test]
    public function getBenefitReconciliation_givenAnAdminAccount_excludesItFromTheReport(): void
    {
        // Arrange - User::getPatreonBenefits() answers with every benefit key for an admin, so without
        // the exclusion every admin reads as maximally over-entitled and buries the real findings
        $link = $this->createLinkedUser();
        $this->grantBenefits($link, [PatreonBenefit::AD_FREE]);
        User::query()->whereKey($link->user_id)->first()->addRole(Role::firstWhere('name', Role::ROLE_ADMIN));

        // Act
        $reconciliation = $this->reconcile([]);

        // Assert
        $this->assertNull($this->findHolder($reconciliation, $link->id));
    }

    #[Test]
    public function getBenefitReconciliation_givenAMemberWhoDowngraded_countsThemWithoutListingThem(): void
    {
        // Arrange - holds both benefits but now pays for the cheaper tier, which grants only one. The
        // next hourly sync revokes the difference by itself, so this needs a number and not a name
        $link = $this->createLinkedUser();
        $this->grantBenefits($link, [PatreonBenefit::AD_FREE, PatreonBenefit::UNLISTED_ROUTES]);

        // Act
        $reconciliation = $this->reconcile([$this->member($link->email, ['2971576'])]);

        // Assert
        $this->assertSame(1, $reconciliation->downgradedCount);
        $this->assertNull($this->findHolder($reconciliation, $link->id));
    }

    #[Test]
    public function getBenefitReconciliation_givenAMemberOnAnUndescribedTier_reportsThemAsBlockedNotUnmatched(): void
    {
        // Arrange - the sync bails out on an unresolvable tier rather than compute an empty benefit set
        // (#4373). Reporting that as "the campaign dropped them" would name entirely the wrong cause
        $link = $this->createLinkedUser();
        $this->grantBenefits($link, [PatreonBenefit::AD_FREE]);

        // Act
        $reconciliation = $this->reconcile([$this->member($link->email, ['9999999'])]);

        // Assert
        $holder = $this->findHolder($reconciliation, $link->id);
        $this->assertNotNull($holder);
        $this->assertSame(PatreonOverEntitlementReason::SyncBlockedUnknownTiers, $holder->reason);
        $this->assertContains($holder, $reconciliation->blockedHolders);
        $this->assertNotContains($holder, $reconciliation->unmatchedHolders);
    }

    #[Test]
    public function getBenefitReconciliation_givenALinkHoldingNoBenefits_leavesItOutOfTheReport(): void
    {
        // Arrange - a linked account that never received anything cannot be over-entitled
        $link = $this->createLinkedUser();

        // Act
        $reconciliation = $this->reconcile([]);

        // Assert
        $this->assertNull($this->findHolder($reconciliation, $link->id));
    }

    #[Test]
    public function getBenefitReconciliation_givenATruncatedMemberList_returnsNull(): void
    {
        // Arrange - the critical guard. On a partial member list every member the fetch never reached
        // looks like an account the campaign has dropped, so a truncated fetch would not merely
        // understate this report - it would fabricate its entire finding (#4373)
        $link = $this->createLinkedUser();
        $this->grantBenefits($link, [PatreonBenefit::AD_FREE]);

        // Act
        $reconciliation = $this->reconcile([], truncated: true);

        // Assert
        $this->assertNull($reconciliation);
    }

    /**
     * @param array<int, array<string, mixed>> $members
     */
    private function reconcile(array $members, bool $truncated = false): ?PatreonBenefitReconciliation
    {
        // Only the loaders are replaced - planPaidBenefitsForMember() below is the real one, so the
        // report's member matching cannot drift away from what the hourly sync actually does
        $patreonService = $this->getMockBuilderPublic(PatreonService::class)
            ->setConstructorArgs([
                $this->createMockPublic(PatreonApiServiceInterface::class),
                $this->createMockPublic(PatreonServiceLoggingInterface::class),
            ])
            ->onlyMethods(['loadCampaignBenefits', 'loadCampaignTiers', 'loadCampaignMembers'])
            ->getMock();

        $patreonService->method('loadCampaignBenefits')->willReturn(self::CAMPAIGN_BENEFITS);
        $patreonService->method('loadCampaignTiers')->willReturn(self::CAMPAIGN_TIERS);
        $patreonService->method('loadCampaignMembers')->willReturn(
            new PatreonCampaignMembers($members, 1, count($members), $truncated),
        );

        $this->app->instance(PatreonServiceInterface::class, $patreonService);

        return app(PatreonDiagnosticsServiceInterface::class)->getBenefitReconciliation();
    }

    /**
     * The report is drawn from every benefit-holding link in the database, so another test's leftovers
     * would move any absolute count. Locating this test's own link is the assertion that holds either way.
     */
    private function findHolder(?PatreonBenefitReconciliation $reconciliation, int $linkId): ?PatreonBenefitHolderDiagnostics
    {
        if ($reconciliation === null) {
            return null;
        }

        foreach ([...$reconciliation->unmatchedHolders, ...$reconciliation->blockedHolders] as $holder) {
            if ($holder->patreonUserLinkId === $linkId) {
                return $holder;
            }
        }

        return null;
    }

    /**
     * @param  array<int, string>   $entitledTierIds
     * @return array<string, mixed>
     */
    private function member(string $email, array $entitledTierIds): array
    {
        return [
            'id'            => sprintf('member-%s', $email),
            'type'          => 'member',
            'attributes'    => ['email' => $email],
            'relationships' => ['currently_entitled_tiers' => ['data' => array_map(
                static fn(string $tierId): array => ['id' => $tierId, 'type' => 'tier'],
                $entitledTierIds,
            )]],
        ];
    }

    private function createLinkedUser(bool $manuallyGranted = false): PatreonUserLink
    {
        $user                   = User::factory()->create();
        $this->createdUserIds[] = $user->id;

        $factory = PatreonUserLink::factory();
        $link    = ($manuallyGranted ? $factory->manuallyGranted() : $factory)->create(['user_id' => $user->id]);

        $this->createdLinkIds[] = $link->id;

        $user->update(['patreon_user_link_id' => $link->id]);

        return $link;
    }

    /**
     * @param array<int, string> $benefits
     */
    private function grantBenefits(PatreonUserLink $patreonUserLink, array $benefits): void
    {
        foreach ($benefits as $benefit) {
            PatreonUserBenefit::create([
                'patreon_user_link_id' => $patreonUserLink->id,
                'patreon_benefit_id'   => PatreonBenefit::ALL[$benefit],
            ]);
        }
    }
}
