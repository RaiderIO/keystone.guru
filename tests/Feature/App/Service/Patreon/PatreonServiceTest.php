<?php

namespace Tests\Feature\App\Service\Patreon;

use App\Models\Patreon\PatreonBenefit;
use App\Models\Patreon\PatreonUserBenefit;
use App\Models\Patreon\PatreonUserLink;
use App\Models\User;
use App\Service\Patreon\PatreonServiceInterface;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('Patreon')]
final class PatreonServiceTest extends PublicTestCase
{
    private const string PATRON_EMAIL = 'patreonservicetest@keystone.guru';

    /** A benefit title that is not in PatreonBenefit::ALL - i.e. one that was renamed or added on patreon.com */
    private const string UNKNOWN_BENEFIT = 'some-benefit-we-do-not-know-about';

    private ?User $user = null;

    private ?PatreonUserLink $patreonUserLink = null;

    #[\Override]
    protected function tearDown(): void
    {
        try {
            if ($this->patreonUserLink !== null) {
                PatreonUserBenefit::query()->where('patreon_user_link_id', $this->patreonUserLink->id)->delete();
                $this->user?->update(['patreon_user_link_id' => null]);
                $this->patreonUserLink->delete();
            }

            $this->user?->delete();
        } finally {
            parent::tearDown();
        }
    }

    #[Test]
    public function applyPaidBenefitsForMember_givenOnlyKnownBenefitTitles_appliesThem(): void
    {
        // Arrange
        $this->createPatron();

        $campaignBenefits = [
            ['id' => '10', 'type' => 'benefit', 'attributes' => ['title' => PatreonBenefit::AD_FREE]],
        ];
        $campaignTiers = $this->campaignTiersWithBenefitIds(['10']);

        // Act
        $result = $this->applyPaidBenefitsForMember($campaignBenefits, $campaignTiers);

        // Assert
        $this->assertTrue($result);
        $this->assertDatabaseHas('patreon_user_benefits', [
            'patreon_user_link_id' => $this->patreonUserLink->id,
            'patreon_benefit_id'   => PatreonBenefit::ALL[PatreonBenefit::AD_FREE],
        ]);
    }

    #[Test]
    public function applyPaidBenefitsForMember_givenAnUnknownBenefitTitle_skipsTheMemberInsteadOfThrowing(): void
    {
        // Arrange
        $this->createPatron();

        $campaignBenefits = [
            ['id' => '10', 'type' => 'benefit', 'attributes' => ['title' => PatreonBenefit::AD_FREE]],
            ['id' => '11', 'type' => 'benefit', 'attributes' => ['title' => self::UNKNOWN_BENEFIT]],
        ];
        $campaignTiers = $this->campaignTiersWithBenefitIds(['10', '11']);

        // Act
        $result = $this->applyPaidBenefitsForMember($campaignBenefits, $campaignTiers);

        // Assert
        $this->assertFalse($result);
        $this->assertSame(0, PatreonUserBenefit::query()
            ->where('patreon_user_link_id', $this->patreonUserLink->id)
            ->count());
    }

    #[Test]
    public function applyPaidBenefitsForMember_givenARenamedBenefitTitle_doesNotRevokeTheBenefitItWasRenamedFrom(): void
    {
        // Arrange
        $this->createPatron();

        $patreonUserBenefit = PatreonUserBenefit::create([
            'patreon_user_link_id' => $this->patreonUserLink->id,
            'patreon_benefit_id'   => PatreonBenefit::ALL[PatreonBenefit::AD_FREE],
        ]);

        // The patron keeps paying for the exact same tier - its benefit was merely renamed on patreon.com, so the
        // benefits we compute are incomplete and must not be used to revoke anything
        $campaignBenefits = [
            ['id' => '10', 'type' => 'benefit', 'attributes' => ['title' => self::UNKNOWN_BENEFIT]],
        ];
        $campaignTiers = $this->campaignTiersWithBenefitIds(['10']);

        // Act
        $result = $this->applyPaidBenefitsForMember($campaignBenefits, $campaignTiers);

        // Assert
        $this->assertFalse($result);
        $this->assertDatabaseHas('patreon_user_benefits', ['id' => $patreonUserBenefit->id]);
    }

    private function createPatron(): void
    {
        $this->user = User::factory()->create();

        $this->patreonUserLink = PatreonUserLink::create([
            'user_id'       => $this->user->id,
            'email'         => self::PATRON_EMAIL,
            'scope'         => 'identity',
            'access_token'  => 'access-token',
            'refresh_token' => 'refresh-token',
            'version'       => 2,
            'expires_at'    => Carbon::now()->addDay()->toDateTimeString(),
        ]);
        $this->user->update(['patreon_user_link_id' => $this->patreonUserLink->id]);
    }

    /**
     * @param  array<int, string>               $benefitIds
     * @return array<int, array<string, mixed>>
     */
    private function campaignTiersWithBenefitIds(array $benefitIds): array
    {
        return [
            [
                'id'            => '1',
                'type'          => 'tier',
                'relationships' => [
                    'benefits' => ['data' => array_map(static fn(string $benefitId) => ['id' => $benefitId], $benefitIds)],
                ],
            ],
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $campaignBenefits
     * @param array<int, array<string, mixed>> $campaignTiers
     */
    private function applyPaidBenefitsForMember(array $campaignBenefits, array $campaignTiers): bool
    {
        $member = [
            'id'            => 'member-1',
            'type'          => 'member',
            'attributes'    => ['email' => self::PATRON_EMAIL],
            'relationships' => ['currently_entitled_tiers' => ['data' => [['id' => '1', 'type' => 'tier']]]],
        ];

        /** @var PatreonServiceInterface $patreonService */
        $patreonService = $this->app->make(PatreonServiceInterface::class);

        return $patreonService->applyPaidBenefitsForMember($campaignBenefits, $campaignTiers, $member);
    }
}
