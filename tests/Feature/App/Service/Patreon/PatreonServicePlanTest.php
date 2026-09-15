<?php

namespace Tests\Feature\App\Service\Patreon;

use App\Models\Patreon\PatreonBenefit;
use App\Models\Patreon\PatreonUserBenefit;
use App\Models\Patreon\PatreonUserLink;
use App\Models\User;
use App\Service\Patreon\Dtos\ApplyPaidBenefitsForMemberResult;
use App\Service\Patreon\PatreonServiceInterface;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

/**
 * The benefit computation the hourly sync runs on every campaign member, and the writes it performs
 * from it.
 *
 * The cases that matter most here are the ones that must NOT revoke: #4373 was a paying patron losing
 * out silently, and every "we could not work this out" path is one benefit-deleting query away from
 * doing that to everyone at once.
 */
#[Group('Patreon')]
#[Group('PatreonService')]
final class PatreonServicePlanTest extends PublicTestCase
{
    /** The benefits the tier below grants, as Patreon reports them - ids are strings in JSON:API. */
    private const array CAMPAIGN_BENEFITS = [
        ['id' => '367345', 'type' => 'benefit', 'attributes' => ['title' => PatreonBenefit::AD_FREE]],
        ['id' => '367914', 'type' => 'benefit', 'attributes' => ['title' => PatreonBenefit::UNLISTED_ROUTES]],
    ];

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
    ];

    private ?User $user = null;

    private ?PatreonUserLink $patreonUserLink = null;

    #[\Override]
    protected function tearDown(): void
    {
        try {
            if ($this->patreonUserLink !== null) {
                PatreonUserBenefit::query()->where('patreon_user_link_id', $this->patreonUserLink->id)->delete();
                $this->patreonUserLink->delete();
            }
            $this->user?->delete();
        } finally {
            parent::tearDown();
        }
    }

    #[Test]
    public function planPaidBenefitsForMember_givenAnEntitledTier_resolvesItsBenefitsDespiteStringIds(): void
    {
        // Arrange - JSON:API ids are strings; comparing them against an int would find no tier at all
        $this->createLinkedUser();

        // Act
        $plan = $this->plan($this->member(['2971575']));

        // Assert
        $this->assertSame(ApplyPaidBenefitsForMemberResult::Applied, $plan->result);
        $this->assertSame([], $plan->unresolvedTierIds);
        $this->assertEqualsCanonicalizing(
            [PatreonBenefit::AD_FREE, PatreonBenefit::UNLISTED_ROUTES],
            $plan->resolvedBenefits,
        );
        $this->assertEqualsCanonicalizing(
            [PatreonBenefit::AD_FREE, PatreonBenefit::UNLISTED_ROUTES],
            $plan->benefitsToAdd,
        );
    }

    #[Test]
    public function planPaidBenefitsForMember_givenATierTheCampaignDoesNotDescribe_returnsUnknownTiers(): void
    {
        // Arrange
        $this->createLinkedUser();

        // Act
        $plan = $this->plan($this->member(['9999999']));

        // Assert
        $this->assertSame(ApplyPaidBenefitsForMemberResult::UnknownTiers, $plan->result);
        $this->assertSame(['9999999'], $plan->unresolvedTierIds);
        $this->assertSame([], $plan->benefitsToRevoke);
    }

    #[Test]
    public function applyPaidBenefitsForMember_givenATierTheCampaignDoesNotDescribe_leavesExistingBenefitsAlone(): void
    {
        // Arrange - an unresolvable tier computes to an empty benefit set, which must not read as
        // "unsubscribed"
        $this->createLinkedUser();
        $this->grantBenefit(PatreonBenefit::AD_FREE);

        // Act
        $result = $this->service()->applyPaidBenefitsForMember(
            self::CAMPAIGN_BENEFITS,
            self::CAMPAIGN_TIERS,
            $this->member(['9999999']),
        );

        // Assert
        $this->assertSame(ApplyPaidBenefitsForMemberResult::UnknownTiers, $result);
        $this->assertSame(
            1,
            PatreonUserBenefit::query()->where('patreon_user_link_id', $this->patreonUserLink->id)->count(),
            'An unresolvable tier must never revoke a benefit the patron is still paying for',
        );
    }

    #[Test]
    public function planPaidBenefitsForMember_givenABenefitTitleMissingFromPatreonBenefitAll_returnsUnknownBenefits(): void
    {
        // Arrange - a renamed or newly added benefit on patreon.com (#3748)
        $this->createLinkedUser();

        $campaignBenefits   = self::CAMPAIGN_BENEFITS;
        $campaignBenefits[] = ['id' => '111', 'type' => 'benefit', 'attributes' => ['title' => 'brand-new-benefit']];

        $campaignTiers                                           = self::CAMPAIGN_TIERS;
        $campaignTiers[0]['relationships']['benefits']['data'][] = ['id' => '111', 'type' => 'benefit'];

        // Act
        $plan = $this->service()->planPaidBenefitsForMember($campaignBenefits, $campaignTiers, $this->member(['2971575']));

        // Assert
        $this->assertSame(ApplyPaidBenefitsForMemberResult::UnknownBenefits, $plan->result);
        $this->assertSame(['brand-new-benefit'], $plan->unknownBenefits);
        $this->assertSame([], $plan->benefitsToRevoke);
    }

    #[Test]
    public function applyPaidBenefitsForMember_givenNoEntitledTiers_revokesEverything(): void
    {
        // Arrange - the genuine unsubscribe, which must keep working
        $this->createLinkedUser();
        $this->grantBenefit(PatreonBenefit::AD_FREE);

        // Act
        $result = $this->service()->applyPaidBenefitsForMember(
            self::CAMPAIGN_BENEFITS,
            self::CAMPAIGN_TIERS,
            $this->member([]),
        );

        // Assert
        $this->assertSame(ApplyPaidBenefitsForMemberResult::Applied, $result);
        $this->assertSame(0, PatreonUserBenefit::query()->where('patreon_user_link_id', $this->patreonUserLink->id)->count());
    }

    #[Test]
    public function applyPaidBenefitsForMember_givenAnEntitledTier_grantsTheBenefitsAndStampsTheLink(): void
    {
        // Arrange
        $this->createLinkedUser();

        // Act
        $result = $this->service()->applyPaidBenefitsForMember(
            self::CAMPAIGN_BENEFITS,
            self::CAMPAIGN_TIERS,
            $this->member(['2971575']),
        );

        // Assert
        $this->assertSame(ApplyPaidBenefitsForMemberResult::Applied, $result);
        $this->assertSame(2, PatreonUserBenefit::query()->where('patreon_user_link_id', $this->patreonUserLink->id)->count());

        // Without the stamp there is no way to tell "the sync decided nothing had to change" from
        // "the sync never reached this patron"
        $this->patreonUserLink->refresh();
        $this->assertNotNull($this->patreonUserLink->last_seen_at);
        $this->assertSame(ApplyPaidBenefitsForMemberResult::Applied, $this->patreonUserLink->last_sync_result);
    }

    #[Test]
    public function planPaidBenefitsForMember_givenAManuallyGrantedLink_appliesNothing(): void
    {
        // Arrange
        $this->createLinkedUser(manuallyGranted: true);

        // Act
        $plan = $this->plan($this->member([]));

        // Assert
        $this->assertSame(ApplyPaidBenefitsForMemberResult::Applied, $plan->result);
        $this->assertTrue($plan->manuallyGranted);
        $this->assertFalse($plan->changesAnything());
    }

    #[Test]
    public function planPaidBenefitsForMember_givenAnEmailNoLinkMatches_returnsMemberNotLinked(): void
    {
        // Arrange - by far the most common outcome, and indistinguishable from a patron whose Patreon
        // email drifted away from the one stored at link time
        $plan = $this->plan([
            'id'            => 'member-1',
            'type'          => 'member',
            'attributes'    => ['email' => 'nobody-has-this@example.test'],
            'relationships' => ['currently_entitled_tiers' => ['data' => []]],
        ]);

        // Assert
        $this->assertSame(ApplyPaidBenefitsForMemberResult::MemberNotLinked, $plan->result);
        $this->assertNull($plan->patreonUserLink);
    }

    #[Test]
    public function planPaidBenefitsForMember_givenAMemberWithoutAnEmailAttribute_returnsMemberNotLinked(): void
    {
        // Arrange - the attribute can be absent entirely rather than null (#3767)
        $plan = $this->plan([
            'id'            => 'member-1',
            'type'          => 'member',
            'attributes'    => [],
            'relationships' => ['currently_entitled_tiers' => ['data' => []]],
        ]);

        // Assert
        $this->assertSame(ApplyPaidBenefitsForMemberResult::MemberNotLinked, $plan->result);
    }

    private function service(): PatreonServiceInterface
    {
        return app(PatreonServiceInterface::class);
    }

    /**
     * @param array<string, mixed> $member
     */
    private function plan(array $member): \App\Service\Patreon\Dtos\PatreonMemberSyncPlan
    {
        return $this->service()->planPaidBenefitsForMember(self::CAMPAIGN_BENEFITS, self::CAMPAIGN_TIERS, $member);
    }

    /**
     * @param  array<int, string>   $entitledTierIds
     * @return array<string, mixed>
     */
    private function member(array $entitledTierIds): array
    {
        return [
            'id'            => 'member-1',
            'type'          => 'member',
            'attributes'    => ['email' => $this->patreonUserLink === null ? 'nobody@example.test' : $this->patreonUserLink->email],
            'relationships' => ['currently_entitled_tiers' => ['data' => array_map(
                static fn(string $tierId): array => ['id' => $tierId, 'type' => 'tier'],
                $entitledTierIds,
            )]],
        ];
    }

    private function createLinkedUser(bool $manuallyGranted = false): void
    {
        $this->user = User::factory()->create();

        $factory               = PatreonUserLink::factory();
        $this->patreonUserLink = ($manuallyGranted ? $factory->manuallyGranted() : $factory)
            ->create(['user_id' => $this->user->id]);

        $this->user->update(['patreon_user_link_id' => $this->patreonUserLink->id]);
    }

    private function grantBenefit(string $benefit): void
    {
        PatreonUserBenefit::create([
            'patreon_user_link_id' => $this->patreonUserLink->id,
            'patreon_benefit_id'   => PatreonBenefit::ALL[$benefit],
        ]);
    }
}
