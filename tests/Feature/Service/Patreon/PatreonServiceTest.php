<?php

namespace Tests\Feature\Service\Patreon;

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

    private const string UNKNOWN_BENEFIT = 'some-benefit-we-do-not-know-about';

    #[Test]
    public function applyPaidBenefitsForMember_givenAnUnknownBenefitTitle_appliesTheKnownBenefitsAndSkipsTheUnknownOne(): void
    {
        // Arrange
        $user            = null;
        $patreonUserLink = null;

        try {
            $user = User::factory()->create();

            $patreonUserLink = PatreonUserLink::create([
                'user_id'       => $user->id,
                'email'         => self::PATRON_EMAIL,
                'scope'         => 'identity',
                'access_token'  => 'access-token',
                'refresh_token' => 'refresh-token',
                'version'       => 2,
                'expires_at'    => Carbon::now()->addDay()->toDateTimeString(),
            ]);
            $user->update(['patreon_user_link_id' => $patreonUserLink->id]);

            // The campaign hands out a benefit we know about and one that was renamed/added on patreon.com since
            $campaignBenefits = [
                ['id' => '10', 'type' => 'benefit', 'attributes' => ['title' => PatreonBenefit::AD_FREE]],
                ['id' => '11', 'type' => 'benefit', 'attributes' => ['title' => self::UNKNOWN_BENEFIT]],
            ];
            $campaignTiers = [
                [
                    'id'            => '1',
                    'type'          => 'tier',
                    'relationships' => ['benefits' => ['data' => [['id' => '10'], ['id' => '11']]]],
                ],
            ];
            $member = [
                'id'            => 'member-1',
                'type'          => 'member',
                'attributes'    => ['email' => self::PATRON_EMAIL],
                'relationships' => ['currently_entitled_tiers' => ['data' => [['id' => '1', 'type' => 'tier']]]],
            ];

            /** @var PatreonServiceInterface $patreonService */
            $patreonService = $this->app->make(PatreonServiceInterface::class);

            // Act
            $result = $patreonService->applyPaidBenefitsForMember($campaignBenefits, $campaignTiers, $member);

            // Assert
            $this->assertTrue($result);
            $this->assertDatabaseHas('patreon_user_benefits', [
                'patreon_user_link_id' => $patreonUserLink->id,
                'patreon_benefit_id'   => PatreonBenefit::ALL[PatreonBenefit::AD_FREE],
            ]);
            $this->assertSame(1, PatreonUserBenefit::query()
                ->where('patreon_user_link_id', $patreonUserLink->id)
                ->count());
        } finally {
            if ($patreonUserLink !== null) {
                PatreonUserBenefit::query()->where('patreon_user_link_id', $patreonUserLink->id)->delete();
                $user?->update(['patreon_user_link_id' => null]);
                $patreonUserLink->delete();
            }

            $user?->delete();
        }
    }
}
