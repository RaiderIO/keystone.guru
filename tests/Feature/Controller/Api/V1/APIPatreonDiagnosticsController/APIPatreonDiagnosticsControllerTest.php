<?php

namespace Tests\Feature\Controller\Api\V1\APIPatreonDiagnosticsController;

use App\Models\Laratrust\Role;
use App\Models\Patreon\PatreonBenefit;
use App\Models\Patreon\PatreonSyncRun;
use App\Models\Patreon\PatreonUserBenefit;
use App\Models\Patreon\PatreonUserLink;
use App\Models\User;
use App\Service\Patreon\Dtos\ApplyPaidBenefitsForMemberResult;
use App\Service\Patreon\Dtos\PatreonCampaignMembers;
use App\Service\Patreon\PatreonServiceInterface;
use Carbon\Carbon;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Teapot\StatusCode;
use Tests\TestCases\PublicTestCase;

/**
 * The read-only Patreon sync diagnostics (#4373).
 *
 * Two properties are load-bearing beyond "does it return data": the endpoints are open to the
 * `ai_agent` role, so they must be closed to everyone else, and no patron email may leave them
 * unmasked - that combination is what makes them safe to call from a local dev machine.
 */
#[Group('Controller')]
#[Group('API')]
#[Group('Patreon')]
#[Group('APIPatreonDiagnostics')]
final class APIPatreonDiagnosticsControllerTest extends PublicTestCase
{
    private const string CAMPAIGN_EMAIL = 'patron-4373@example.test';

    private ?User $user = null;

    private ?PatreonUserLink $patreonUserLink = null;

    /** @var array<int, int> */
    private array $createdSyncRunIds = [];

    #[\Override]
    protected function tearDown(): void
    {
        try {
            if ($this->patreonUserLink !== null) {
                PatreonUserBenefit::query()->where('patreon_user_link_id', $this->patreonUserLink->id)->delete();
                $this->patreonUserLink->delete();
            }
            $this->user?->delete();
            if ($this->createdSyncRunIds !== []) {
                PatreonSyncRun::query()->whereIn('id', $this->createdSyncRunIds)->delete();
            }
        } finally {
            parent::tearDown();
        }
    }

    #[Test]
    public function syncRuns_givenAdmin_returnsTheRecordedHistoryNewestFirst(): void
    {
        // Arrange
        $this->actingAsAdmin();
        $older = $this->createSyncRun(['started_at' => now()->subHours(2), 'members_fetched' => 400]);
        $newer = $this->createSyncRunTruncated(['started_at' => now()->subHour(), 'members_fetched' => 180]);

        // Act
        $response = $this->getJson(route('api.v1.patreon.sync_runs'));

        // Assert - the drop from 400 to 180 is the whole point of keeping this history. Asserted over this
        // class's own two runs rather than over data.0/data.1: the endpoint returns every run in the schema,
        // so any other run recorded within the last two hours would shift every index
        $response->assertOk();
        $ownRuns = $this->ownSyncRunsFrom($response->json('data'));
        $this->assertSame([$newer->id, $older->id], array_column($ownRuns, 'id'), 'newest first');
        $this->assertSame(180, $ownRuns[0]['members_fetched']);
        $this->assertTrue($ownRuns[0]['truncated']);
        $this->assertSame(400, $ownRuns[1]['members_fetched']);
    }

    #[Test]
    public function syncRuns_givenAiAgent_returnsOk(): void
    {
        // Arrange - the role the local tooling authenticates as against production
        $aiAgent = User::factory()->create();

        try {
            $aiAgent->addRole(Role::ROLE_AI_AGENT);
            $this->actingAs($aiAgent);

            // Act
            $response = $this->getJson(route('api.v1.patreon.sync_runs'));

            // Assert
            $response->assertOk();
        } finally {
            $aiAgent->delete();
        }
    }

    #[Test]
    public function syncRuns_givenAuthenticatedNonAdmin_returnsForbidden(): void
    {
        // Arrange
        $nonAdmin = User::factory()->create();

        try {
            $this->actingAs($nonAdmin);

            // Act
            $response = $this->getJson(route('api.v1.patreon.sync_runs'));

            // Assert
            $response->assertStatus(StatusCode::FORBIDDEN);
        } finally {
            $nonAdmin->delete();
        }
    }

    #[Test]
    public function user_givenAnUnknownAccount_returnsUnprocessable(): void
    {
        // Arrange
        $this->actingAsAdmin();
        $this->mockPatreonService();

        // Act
        $response = $this->getJson(route('api.v1.patreon.user', ['username' => 'nobody-by-this-name-4373']));

        // Assert
        $response->assertUnprocessable();
    }

    #[Test]
    public function user_givenNoIdentifierAtAll_returnsUnprocessable(): void
    {
        // Arrange
        $this->actingAsAdmin();
        $this->mockPatreonService();

        // Act
        $response = $this->getJson(route('api.v1.patreon.user'));

        // Assert
        $response->assertUnprocessable();
    }

    #[Test]
    public function user_givenALinkedAccountTheCampaignLists_reportsTheSyncPlanWithAMaskedEmail(): void
    {
        // Arrange
        $this->actingAsAdmin();
        $this->createLinkedUser(self::CAMPAIGN_EMAIL);
        $this->mockPatreonService([$this->campaignMember(self::CAMPAIGN_EMAIL, ['2971575'])]);

        // Act
        $response = $this->getJson(route('api.v1.patreon.user', ['user_id' => $this->user->id]));

        // Assert
        $response->assertOk();
        $response->assertJsonPath('data.user_id', $this->user->id);
        $response->assertJsonPath('data.member.result', ApplyPaidBenefitsForMemberResult::Applied->name);
        $response->assertJsonPath('data.member.benefits_to_add', [PatreonBenefit::AD_FREE]);

        // No response may carry a usable address, for any caller
        $body = $response->json();
        $this->assertStringNotContainsString(self::CAMPAIGN_EMAIL, json_encode($body));
        $this->assertNotNull($response->json('data.link_email'));
    }

    #[Test]
    public function user_givenACampaignEmailMatchingTheAccountButNotTheLink_reportsEmailDrift(): void
    {
        // Arrange - the patron changed their Patreon email after linking, so the sync stopped matching
        // them and has reported MemberNotLinked ever since, which looks exactly like never having linked
        $this->actingAsAdmin();
        $this->createLinkedUser('stale-link-email-4373@other-provider.test');
        $this->mockPatreonService([$this->campaignMember($this->user->email, ['2971575'])]);

        // Act
        $response = $this->getJson(route('api.v1.patreon.user', ['user_id' => $this->user->id]));

        // Assert
        $response->assertOk();
        $response->assertJsonPath('data.member', null);
        $driftCandidate = (string)$response->json('data.email_drift_candidate');
        $this->assertNotSame('', $driftCandidate);
        $this->assertStringNotContainsString($this->user->email, $driftCandidate);

        // The domain survives masking, which is what makes the drift readable against the link's email
        $this->assertStringEndsWith((string)mb_strstr($this->user->email, '@'), $driftCandidate);
        $this->assertStringNotContainsString('other-provider.test', $driftCandidate);
    }

    #[Test]
    public function user_givenALinkNotSeenSinceTheLatestRun_reportsItAsMissedByThatRun(): void
    {
        // Arrange - the truncation fingerprint: the sync ran, but never got to this patron's page
        $this->actingAsAdmin();
        $this->createLinkedUser(self::CAMPAIGN_EMAIL);
        $this->patreonUserLink->update(['last_seen_at' => now()->subHours(6)]);
        // The endpoint compares against the single most recent run in the schema, so this one has to win
        // that comparison outright - subMinutes(5) loses it to any run another test left behind since
        $this->createSyncRun(['started_at' => $this->afterEveryRecordedSyncRun()]);
        $this->mockPatreonService();

        // Act
        $response = $this->getJson(route('api.v1.patreon.user', ['user_id' => $this->user->id]));

        // Assert
        $response->assertOk();
        $response->assertJsonPath('data.missed_by_latest_run', true);
    }

    #[Test]
    public function campaign_givenATierGrantingNoBenefits_flagsThatTier(): void
    {
        // Arrange - a paying member of such a tier computes to an empty benefit set, which reads as
        // "unsubscribed" and revokes everything they hold
        $this->actingAsAdmin();
        $this->mockPatreonService(campaignTiers: [
            [
                'id'            => '2971575',
                'type'          => 'tier',
                'attributes'    => ['title' => 'Supporter'],
                'relationships' => ['benefits' => ['data' => [['id' => '367345', 'type' => 'benefit']]]],
            ],
            [
                'id'            => '5555555',
                'type'          => 'tier',
                'attributes'    => ['title' => 'Five dollar tier'],
                'relationships' => ['benefits' => ['data' => []]],
            ],
        ]);

        // Act
        $response = $this->getJson(route('api.v1.patreon.campaign'));

        // Assert
        $response->assertOk();
        $response->assertJsonPath('data.tier_ids_granting_nothing', ['5555555']);
    }

    #[Test]
    public function campaign_givenAnUnloadableCampaign_returnsBadGateway(): void
    {
        // Arrange
        $this->actingAsAdmin();

        $patreonService = $this->createMockPublic(PatreonServiceInterface::class);
        $patreonService->method('loadCampaignBenefits')->willReturn(null);
        $patreonService->method('loadCampaignTiers')->willReturn(null);
        $this->app->instance(PatreonServiceInterface::class, $patreonService);

        // Act
        $response = $this->getJson(route('api.v1.patreon.campaign'));

        // Assert
        $response->assertStatus(StatusCode::BAD_GATEWAY);
    }

    #[Test]
    public function syncDryRun_givenATruncatedMemberFetch_returnsBadGatewayRatherThanPartialConclusions(): void
    {
        // Arrange - every member the fetch never saw would otherwise look like a member who left
        $this->actingAsAdmin();

        $patreonService = $this->createMockPublic(PatreonServiceInterface::class);
        $patreonService->method('loadCampaignBenefits')->willReturn([]);
        $patreonService->method('loadCampaignTiers')->willReturn([]);
        $patreonService->method('loadCampaignMembers')->willReturn(new PatreonCampaignMembers([], 5, 400, truncated: true));
        $this->app->instance(PatreonServiceInterface::class, $patreonService);

        // Act
        $response = $this->getJson(route('api.v1.patreon.sync_dry_run'));

        // Assert
        $response->assertStatus(StatusCode::BAD_GATEWAY);
    }

    #[Test]
    public function syncDryRun_givenAMemberWithAnUnresolvableTier_listsThemAsBlocked(): void
    {
        // Arrange
        $this->actingAsAdmin();
        $this->createLinkedUser(self::CAMPAIGN_EMAIL);
        $this->mockPatreonService([$this->campaignMember(self::CAMPAIGN_EMAIL, ['9999999'])]);

        // Act
        $response = $this->getJson(route('api.v1.patreon.sync_dry_run'));

        // Assert
        $response->assertOk();
        $response->assertJsonPath('data.members_fetched', 1);
        $response->assertJsonPath('data.result_counts.UnknownTiers', 1);
        $response->assertJsonPath('data.members_blocked.0.unresolved_tier_ids', ['9999999']);
    }

    #[Test]
    public function benefitReconciliation_givenAnAccountTheCampaignNoLongerLists_returnsItAsUnmatched(): void
    {
        // Arrange - a patron holding benefits whom the campaign does not mention. The hourly sync only
        // ever walks members, so it can never reach this account to revoke anything (#4386)
        $this->actingAsAdmin();
        $this->createLinkedUser(self::CAMPAIGN_EMAIL);
        PatreonUserBenefit::create([
            'patreon_user_link_id' => $this->patreonUserLink->id,
            'patreon_benefit_id'   => PatreonBenefit::ALL[PatreonBenefit::AD_FREE],
        ]);
        $this->mockPatreonService([$this->campaignMember('somebody-else@example.test', ['2971575'])]);

        // Act
        $response = $this->getJson(route('api.v1.patreon.benefit_reconciliation'));

        // Assert
        $response->assertOk();
        $response->assertJsonPath('data.needs_attention', true);
        // Located by link id rather than asserted at index 0: the list covers every benefit-holding link in
        // the schema in no defined order, so any other such link decides what sits at index 0
        $holder = $this->findUnmatchedHolder($response->json('data.unmatched_holders'), $this->patreonUserLink->id);
        $this->assertNotNull($holder, 'this test\'s link must be reported as unmatched');
        $this->assertSame('no_campaign_member', $holder['reason']);
        $this->assertSame([PatreonBenefit::AD_FREE], $holder['stored_benefits']);

        // The masked address is the same guarantee the other endpoints make - this one returns a list of
        // accounts rather than taking one as input, so it is the endpoint where that matters most
        $response->assertJsonMissing(['masked_link_email' => self::CAMPAIGN_EMAIL]);
    }

    #[Test]
    public function benefitReconciliation_givenATruncatedMemberFetch_returnsBadGatewayRatherThanEveryAccountAtOnce(): void
    {
        // Arrange - on a partial member list every account the fetch never reached looks like one the
        // campaign has dropped, so a truncated fetch would fabricate this report's entire finding
        $this->actingAsAdmin();

        $patreonService = $this->createMockPublic(PatreonServiceInterface::class);
        $patreonService->method('loadCampaignBenefits')->willReturn([]);
        $patreonService->method('loadCampaignTiers')->willReturn([]);
        $patreonService->method('loadCampaignMembers')->willReturn(new PatreonCampaignMembers([], 5, 400, truncated: true));
        $this->app->instance(PatreonServiceInterface::class, $patreonService);

        // Act
        $response = $this->getJson(route('api.v1.patreon.benefit_reconciliation'));

        // Assert
        $response->assertStatus(StatusCode::BAD_GATEWAY);
    }

    #[Test]
    public function benefitReconciliation_givenAuthenticatedNonAdmin_returnsForbidden(): void
    {
        // Arrange - this is the one endpoint here that lists accounts rather than taking one as input
        $nonAdmin = User::factory()->create();

        try {
            $this->actingAs($nonAdmin);

            // Act
            $response = $this->getJson(route('api.v1.patreon.benefit_reconciliation'));

            // Assert
            $response->assertStatus(StatusCode::FORBIDDEN);
        } finally {
            $nonAdmin->delete();
        }
    }

    /**
     * Binds a PatreonService whose campaign is a single tier granting ad-free, plus whatever members are
     * handed in. The plan computation itself is deliberately NOT mocked - these tests assert on what a
     * real sync would decide.
     *
     * @param array<int, array<string, mixed>>      $members
     * @param array<int, array<string, mixed>>|null $campaignTiers
     */
    private function mockPatreonService(array $members = [], ?array $campaignTiers = null): void
    {
        $realPatreonService = app(PatreonServiceInterface::class);

        $patreonService = $this->createMockPublic(PatreonServiceInterface::class);
        $patreonService->method('loadCampaignBenefits')->willReturn([
            ['id' => '367345', 'type' => 'benefit', 'attributes' => ['title' => PatreonBenefit::AD_FREE]],
        ]);
        $patreonService->method('loadCampaignTiers')->willReturn($campaignTiers ?? [
            [
                'id'            => '2971575',
                'type'          => 'tier',
                'attributes'    => ['title' => 'Supporter'],
                'relationships' => ['benefits' => ['data' => [['id' => '367345', 'type' => 'benefit']]]],
            ],
        ]);
        $patreonService->method('loadCampaignMembers')->willReturn(new PatreonCampaignMembers($members, 1, count($members), truncated: false));
        $patreonService->method('planPaidBenefitsForMember')
            ->willReturnCallback(static fn(array $benefits, array $tiers, array $member) => $realPatreonService
                ->planPaidBenefitsForMember($benefits, $tiers, $member));

        $this->app->instance(PatreonServiceInterface::class, $patreonService);
    }

    /**
     * @param  array<int, string>   $entitledTierIds
     * @return array<string, mixed>
     */
    private function campaignMember(string $email, array $entitledTierIds): array
    {
        return [
            'id'            => 'member-4373',
            'type'          => 'member',
            'attributes'    => ['email' => $email, 'patron_status' => 'active_patron', 'last_charge_status' => 'Paid'],
            'relationships' => ['currently_entitled_tiers' => ['data' => array_map(
                static fn(string $tierId): array => ['id' => $tierId, 'type' => 'tier'],
                $entitledTierIds,
            )]],
        ];
    }

    /**
     * @param array<string, mixed> $attributes
     */
    private function createSyncRun(array $attributes): PatreonSyncRun
    {
        $patreonSyncRun            = PatreonSyncRun::factory()->create($attributes);
        $this->createdSyncRunIds[] = $patreonSyncRun->id;

        return $patreonSyncRun;
    }

    /**
     * @param array<string, mixed> $attributes
     */
    private function createSyncRunTruncated(array $attributes): PatreonSyncRun
    {
        $patreonSyncRun            = PatreonSyncRun::factory()->truncated()->create($attributes);
        $this->createdSyncRunIds[] = $patreonSyncRun->id;

        return $patreonSyncRun;
    }

    /**
     * A timestamp no run recorded so far can beat, so a test that needs "the latest run" gets it regardless
     * of what else the shared schema holds.
     */
    private function afterEveryRecordedSyncRun(): Carbon
    {
        $latest = PatreonSyncRun::query()->max('started_at');

        return $latest === null ? now() : Carbon::parse($latest)->addSecond()->max(now());
    }

    /**
     * @param  array<int, array<string, mixed>> $runs
     * @return array<int, array<string, mixed>>
     */
    private function ownSyncRunsFrom(array $runs): array
    {
        return array_values(array_filter(
            $runs,
            fn(array $run): bool => in_array($run['id'], $this->createdSyncRunIds, true),
        ));
    }

    /**
     * @param  array<int, array<string, mixed>> $holders
     * @return array<string, mixed>|null
     */
    private function findUnmatchedHolder(array $holders, int $patreonUserLinkId): ?array
    {
        foreach ($holders as $holder) {
            if ($holder['patreon_user_link_id'] === $patreonUserLinkId) {
                return $holder;
            }
        }

        return null;
    }

    private function createLinkedUser(string $linkEmail): void
    {
        $this->user            = User::factory()->create();
        $this->patreonUserLink = PatreonUserLink::factory()->create([
            'user_id' => $this->user->id,
            'email'   => $linkEmail,
        ]);
        $this->user->update(['patreon_user_link_id' => $this->patreonUserLink->id]);
    }

    private function actingAsAdmin(): void
    {
        /** @var User $admin */
        $admin = User::findOrFail(1);
        $this->assertTrue($admin->hasRole(Role::ROLE_ADMIN), 'User id=1 must have the admin role for this test (seed the database).');
        $this->actingAs($admin);
    }
}
