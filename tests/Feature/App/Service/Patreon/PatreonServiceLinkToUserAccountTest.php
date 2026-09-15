<?php

namespace Tests\Feature\App\Service\Patreon;

use App\Models\Patreon\PatreonBenefit;
use App\Models\Patreon\PatreonUserBenefit;
use App\Models\Patreon\PatreonUserLink;
use App\Models\User;
use App\Repositories\Interfaces\Patreon\PatreonManualGrantRepositoryInterface;
use App\Service\Patreon\Dtos\LinkToUserIdResult;
use App\Service\Patreon\Logging\PatreonServiceLoggingInterface;
use App\Service\Patreon\PatreonApiServiceInterface;
use App\Service\Patreon\PatreonService;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\TestCases\PublicTestCase;

/**
 * The OAuth link flow requests the identity.memberships scope, so the identity response carries the user's
 * membership of every creator they support - not just ours.
 */
#[Group('Patreon')]
final class PatreonServiceLinkToUserAccountTest extends PublicTestCase
{
    private const string CAMPAIGN_ID = '2102279';

    private const string OTHER_CAMPAIGN_ID = '9999999';

    private const string PATRON_EMAIL = 'patreonservicelinktouseraccounttest@keystone.guru';

    private const string OUR_TIER_ID = '2971575';

    private const string OTHER_CAMPAIGN_TIER_ID = '10302662';

    private ?User $user = null;

    private PatreonServiceLoggingInterface&MockObject $log;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        config(['keystoneguru.patreon.campaign_id' => self::CAMPAIGN_ID]);

        $this->log = $this->createMockPublic(PatreonServiceLoggingInterface::class);
    }

    #[\Override]
    protected function tearDown(): void
    {
        try {
            if ($this->user !== null) {
                $patreonUserLinkIds = PatreonUserLink::query()->where('user_id', $this->user->id)->pluck('id');
                PatreonUserBenefit::query()->whereIn('patreon_user_link_id', $patreonUserLinkIds)->delete();
                PatreonUserLink::query()->whereIn('id', $patreonUserLinkIds)->delete();

                $this->user->delete();
            }
        } finally {
            parent::tearDown();
        }
    }

    #[Test]
    public function linkToUserAccount_givenAnotherCampaignsMembershipFirst_appliesTheBenefitsOfOurCampaignsMembership(): void
    {
        // Arrange
        $this->user = User::factory()->create();

        $this->log->expects($this->never())->method('applyPaidBenefitsForMemberUnknownPatreonTiers');

        $patreonService = $this->createPatreonService([
            $this->member('other-member', self::OTHER_CAMPAIGN_ID, self::OTHER_CAMPAIGN_TIER_ID),
            $this->member('our-member', self::CAMPAIGN_ID, self::OUR_TIER_ID),
        ]);

        // Act
        $result = $patreonService->linkToUserAccount($this->user, 'code', 'https://keystone.guru/patreon-link');

        // Assert
        $this->assertSame(LinkToUserIdResult::LinkSuccessful, $result);
        $patreonUserLink = PatreonUserLink::query()->where('user_id', $this->user->id)->firstOrFail();
        $this->assertDatabaseHas('patreon_user_benefits', [
            'patreon_user_link_id' => $patreonUserLink->id,
            'patreon_benefit_id'   => PatreonBenefit::ALL[PatreonBenefit::AD_FREE],
        ]);
    }

    #[Test]
    public function linkToUserAccount_givenOnlyAnotherCampaignsMembership_linksWithoutBenefits(): void
    {
        // Arrange
        $this->user = User::factory()->create();

        $this->log->expects($this->once())->method('linkToUserAccountNotAMemberOfCampaign');
        $this->log->expects($this->never())->method('applyPaidBenefitsForMemberUnknownPatreonTiers');

        $patreonService = $this->createPatreonService([
            $this->member('other-member', self::OTHER_CAMPAIGN_ID, self::OTHER_CAMPAIGN_TIER_ID),
        ]);

        // Act
        $result = $patreonService->linkToUserAccount($this->user, 'code', 'https://keystone.guru/patreon-link');

        // Assert
        $this->assertSame(LinkToUserIdResult::LinkSuccessful, $result);
        $patreonUserLink = PatreonUserLink::query()->where('user_id', $this->user->id)->firstOrFail();
        $this->assertSame(self::PATRON_EMAIL, $patreonUserLink->email);
        $this->assertSame(0, PatreonUserBenefit::query()->where('patreon_user_link_id', $patreonUserLink->id)->count());
    }

    #[Test]
    public function linkToUserAccount_givenNoMemberships_linksWithoutBenefits(): void
    {
        // Arrange
        $this->user = User::factory()->create();

        $this->log->expects($this->once())->method('linkToUserAccountNotAMemberOfCampaign');
        $this->log->expects($this->never())->method('linkToUserAccountException');

        $patreonService = $this->createPatreonService([]);

        // Act
        $result = $patreonService->linkToUserAccount($this->user, 'code', 'https://keystone.guru/patreon-link');

        // Assert
        $this->assertSame(LinkToUserIdResult::LinkSuccessful, $result);
        $this->assertDatabaseHas('patreon_user_links', ['user_id' => $this->user->id, 'email' => self::PATRON_EMAIL]);
    }

    /**
     * @param array<int, array<string, mixed>> $memberships
     */
    private function createPatreonService(array $memberships): PatreonService&MockObject
    {
        $patreonApiService = $this->createMockPublic(PatreonApiServiceInterface::class);
        $patreonApiService->method('getAccessTokenFromCode')->willReturn([
            'scope'         => 'identity identity[email] identity.memberships campaigns',
            'access_token'  => 'access-token',
            'refresh_token' => 'refresh-token',
            'version'       => 2,
            'expires_in'    => 3600,
        ]);
        $patreonApiService->method('getIdentity')->willReturn([
            'data'     => ['id' => 'user-1', 'type' => 'user', 'attributes' => ['email' => self::PATRON_EMAIL]],
            'included' => [
                ['id' => self::CAMPAIGN_ID, 'type' => 'campaign'],
                ['id' => self::OTHER_CAMPAIGN_ID, 'type' => 'campaign'],
                ...$memberships,
            ],
        ]);

        $patreonService = $this->getMockBuilderPublic(PatreonService::class)
            ->setConstructorArgs([
                $patreonApiService,
                $this->app->make(PatreonManualGrantRepositoryInterface::class),
                $this->log,
            ])
            ->onlyMethods(['loadCampaignBenefits', 'loadCampaignTiers'])
            ->getMock();

        $patreonService->method('loadCampaignBenefits')->willReturn([
            ['id' => '367345', 'type' => 'benefit', 'attributes' => ['title' => PatreonBenefit::AD_FREE]],
        ]);
        $patreonService->method('loadCampaignTiers')->willReturn([
            [
                'id'            => self::OUR_TIER_ID,
                'type'          => 'tier',
                'relationships' => ['benefits' => ['data' => [['id' => '367345', 'type' => 'benefit']]]],
            ],
        ]);

        return $patreonService;
    }

    /**
     * A membership as getIdentity() hands it back - with the user's email already copied onto it.
     *
     * @return array<string, mixed>
     */
    private function member(string $memberId, string $campaignId, string $tierId): array
    {
        return [
            'id'            => $memberId,
            'type'          => 'member',
            'attributes'    => ['email' => self::PATRON_EMAIL, 'patron_status' => 'active_patron'],
            'relationships' => [
                'campaign'                 => ['data' => ['id' => $campaignId, 'type' => 'campaign']],
                'currently_entitled_tiers' => ['data' => [['id' => $tierId, 'type' => 'tier']]],
            ],
        ];
    }
}
