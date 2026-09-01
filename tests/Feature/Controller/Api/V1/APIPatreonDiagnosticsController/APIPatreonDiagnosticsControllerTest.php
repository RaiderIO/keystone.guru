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

    #[\Override]
    protected function tearDown(): void
    {
        try {
            if ($this->patreonUserLink !== null) {
                PatreonUserBenefit::query()->where('patreon_user_link_id', $this->patreonUserLink->id)->delete();
                $this->patreonUserLink->delete();
            }
            $this->user?->delete();
            PatreonSyncRun::query()->delete();
        } finally {
            parent::tearDown();
        }
    }

    #[Test]
    public function syncRuns_givenAdmin_returnsTheRecordedHistoryNewestFirst(): void
    {
        // Arrange
        $this->actingAsAdmin();
        PatreonSyncRun::factory()->create(['started_at' => now()->subHours(2), 'members_fetched' => 400]);
        PatreonSyncRun::factory()->truncated()->create(['started_at' => now()->subHour(), 'members_fetched' => 180]);

        // Act
        $response = $this->getJson(route('api.v1.patreon.sync_runs'));

        // Assert - the drop from 400 to 180 is the whole point of keeping this history
        $response->assertOk();
        $response->assertJsonPath('data.0.members_fetched', 180);
        $response->assertJsonPath('data.0.truncated', true);
        $response->assertJsonPath('data.1.members_fetched', 400);
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
        PatreonSyncRun::factory()->create(['started_at' => now()->subMinutes(5)]);
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
