<?php

namespace Tests\Feature\App\Service\Patreon;

use App\Models\Laratrust\Role;
use App\Models\Patreon\PatreonBenefit;
use App\Models\Patreon\PatreonManualGrant;
use App\Models\Patreon\PatreonUserBenefit;
use App\Models\Patreon\PatreonUserLink;
use App\Models\User;
use App\Repositories\Interfaces\Patreon\PatreonManualGrantRepositoryInterface;
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

    /** @var array<int, int> */
    private array $createdGrantIds = [];

    #[Override]
    protected function tearDown(): void
    {
        try {
            if ($this->createdGrantIds !== []) {
                PatreonManualGrant::query()->whereIn('id', $this->createdGrantIds)->delete();
            }

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
        // A link holding no benefit rows is not a holder, so this measures the rest of the schema with
        // everything else about this test already in place
        $baseline = $this->reconcile([$this->member($link->email, ['2971575'])]);
        $this->grantBenefits($link, [PatreonBenefit::AD_FREE, PatreonBenefit::UNLISTED_ROUTES]);

        // Act
        $reconciliation = $this->reconcile([$this->member($link->email, ['2971575'])]);

        // Assert - as deltas against the baseline, and scoped to this test's own links. The counts (and so
        // needsAttention()) are true totals over every benefit-holding link in the schema, so a leftover from
        // an aborted run - or the developer's own Patreon link in a long-lived dev database - would move any
        // absolute assertion. needsAttention()'s own semantics are covered from constructed counts in
        // Tests\Unit\App\Service\Patreon\Dtos\Diagnostics\PatreonBenefitReconciliationTest
        $this->assertNull($this->findHolder($reconciliation, $link->id));
        $this->assertSame(0, $reconciliation->downgradedCount);
        $this->assertNoneOfThisTestsLinksAreReported($reconciliation);
        $this->assertSame($baseline->unmatchedCount, $reconciliation->unmatchedCount, 'a matched link must not be counted as unmatched');
        $this->assertSame($baseline->blockedCount, $reconciliation->blockedCount, 'a matched link must not be counted as blocked');
        $this->assertSame($baseline->holderCount + 1, $reconciliation->holderCount, 'the link must be counted as a holder once it holds benefits');
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
    public function getBenefitReconciliation_givenALinkOrphanedByAUserWhoHeldAManualGrant_stillReportsIt(): void
    {
        // Arrange - User::deleting removes neither the grant record nor the duplicate link, and the grant is
        // keyed on user_id, so the orphaned link would inherit a grant belonging to a user who is gone
        $link = $this->createLinkedUser();
        $this->grantBenefits($link, [PatreonBenefit::AD_FREE]);
        $this->grantManually($link);
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
    public function getBenefitReconciliation_givenARealLinkCarryingAnActiveManualGrant_excludesItFromTheReport(): void
    {
        // Arrange - the second half of PatreonUserLink::getManuallyGrantedAttribute(): a genuine Patreon
        // link whose benefits an admin overrode (#4385). The sync skips these, so an account the campaign
        // no longer lists would otherwise be reported as over-entitled for a state an admin chose
        $link = $this->createLinkedUser();
        $this->grantBenefits($link, [PatreonBenefit::AD_FREE, PatreonBenefit::UNLISTED_ROUTES]);
        $this->grantManually($link);

        // Act - a campaign that does not list them at all
        $reconciliation = $this->reconcile([]);

        // Assert
        $this->assertNull($this->findHolder($reconciliation, $link->id));
    }

    #[Test]
    public function getBenefitReconciliation_givenALinkWhoseManualGrantWasRevoked_reportsItAgain(): void
    {
        // Arrange - the exclusion keys on an *active* grant, so revoking the override must hand the
        // account straight back to the report rather than hiding it forever
        $link = $this->createLinkedUser();
        $this->grantBenefits($link, [PatreonBenefit::AD_FREE]);
        $this->grantManually($link, revoked: true);

        // Act
        $reconciliation = $this->reconcile([]);

        // Assert
        $holder = $this->findHolder($reconciliation, $link->id);
        $this->assertNotNull($holder);
        $this->assertSame(PatreonOverEntitlementReason::NoCampaignMember, $holder->reason);
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
    public function getBenefitReconciliation_givenExcessOnADuplicateLinkTheSyncWillNotClear_reportsItRatherThanCountingItAsADowngrade(): void
    {
        // Arrange - two links for one account. The campaign matches the second by email, but the plan
        // diffs against User::getPatreonBenefits(), which reads the account's patreonUserLink pointer -
        // the first link. So the revoke list describes the first link's rows and is applied to the
        // second's, and the second's excess is never named in it. No number of syncs will clear it
        $pointerLink = $this->createLinkedUser();
        $this->grantBenefits($pointerLink, [PatreonBenefit::AD_FREE]);

        $matchedLink            = PatreonUserLink::factory()->create(['user_id' => $pointerLink->user_id]);
        $this->createdLinkIds[] = $matchedLink->id;
        $this->grantBenefits($matchedLink, [PatreonBenefit::AD_FREE, PatreonBenefit::UNLISTED_ROUTES]);

        // Act - entitled to the cheaper tier, which grants ad-free only
        $reconciliation = $this->reconcile([$this->member($matchedLink->email, ['2971576'])]);

        // Assert - reported by name, not swallowed as a self-correcting downgrade
        $holder = $this->findHolder($reconciliation, $matchedLink->id);
        $this->assertNotNull($holder);
        $this->assertSame(PatreonOverEntitlementReason::DuplicateLinkAmbiguity, $holder->reason);
        $this->assertContains($pointerLink->id, $holder->duplicateLinkIds);
        $this->assertSame(0, $reconciliation->downgradedCount);
        $this->assertTrue($reconciliation->needsAttention());
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
                // The real repository, so a manual grant recorded by the test is one the plan actually sees
                app(PatreonManualGrantRepositoryInterface::class),
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
     * The scoped stand-in for assertFalse($reconciliation->needsAttention()): it still catches a bug that
     * dumps a matched link into a reported bucket, without depending on the rest of the schema being clean.
     */
    private function assertNoneOfThisTestsLinksAreReported(PatreonBenefitReconciliation $reconciliation): void
    {
        $reported = [];
        foreach ([...$reconciliation->unmatchedHolders, ...$reconciliation->blockedHolders] as $holder) {
            if (in_array($holder->patreonUserLinkId, $this->createdLinkIds, true)) {
                $reported[] = $holder->patreonUserLinkId;
            }
        }

        $this->assertSame([], $reported, 'this test\'s own links must not be reported as needing attention');
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

    private function grantManually(PatreonUserLink $patreonUserLink, bool $revoked = false): void
    {
        $factory = PatreonManualGrant::factory();

        $grant                   = ($revoked ? $factory->revoked() : $factory)->create(['user_id' => $patreonUserLink->user_id]);
        $this->createdGrantIds[] = $grant->id;
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
