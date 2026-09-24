<?php

namespace Tests\Feature\App\Models;

use App\Models\Laratrust\Role;
use App\Models\Patreon\PatreonBenefit;
use App\Models\Patreon\PatreonManualGrant;
use App\Models\Patreon\PatreonUserBenefit;
use App\Models\Patreon\PatreonUserLink;
use App\Models\User;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

/**
 * User::isPayingPatron(): only a real, non-overridden Patreon link with benefits counts as paying.
 */
#[Group('User')]
#[Group('Patreon')]
final class UserIsPayingPatronTest extends PublicTestCase
{
    private ?User $user = null;

    #[\Override]
    protected function tearDown(): void
    {
        try {
            if ($this->user !== null) {
                PatreonManualGrant::query()->where('user_id', $this->user->id)->delete();
                $this->user->patreonUserLink()->first()?->delete();
                $this->user->delete();
            }
        } finally {
            parent::tearDown();
        }
    }

    #[Test]
    public function isPayingPatron_givenLinkWithBenefits_returnsTrue(): void
    {
        // Arrange
        $this->user = User::factory()->create();
        $this->createPatreonUserLink($this->user, withBenefit: true);

        // Act
        $result = $this->user->fresh()->isPayingPatron();

        // Assert
        $this->assertTrue($result);
    }

    #[Test]
    public function isPayingPatron_givenNoLink_returnsFalse(): void
    {
        // Arrange
        $this->user = User::factory()->create();

        // Act
        $result = $this->user->fresh()->isPayingPatron();

        // Assert
        $this->assertFalse($result);
    }

    #[Test]
    public function isPayingPatron_givenLinkWithoutBenefits_returnsFalse(): void
    {
        // Arrange
        $this->user = User::factory()->create();
        $this->createPatreonUserLink($this->user, withBenefit: false);

        // Act
        $result = $this->user->fresh()->isPayingPatron();

        // Assert
        $this->assertFalse($result);
    }

    #[Test]
    public function isPayingPatron_givenLinkFabricatedByTheAdminPages_returnsFalse(): void
    {
        // Arrange
        $this->user = User::factory()->create();
        $this->createPatreonUserLink($this->user, withBenefit: true, manuallyGranted: true);

        // Act
        $result = $this->user->fresh()->isPayingPatron();

        // Assert
        $this->assertFalse($result);
    }

    #[Test]
    public function isPayingPatron_givenLinkOverriddenByActiveManualGrant_returnsFalse(): void
    {
        // Arrange
        $this->user = User::factory()->create();
        $this->createPatreonUserLink($this->user, withBenefit: true);
        PatreonManualGrant::factory()->create(['user_id' => $this->user->id]);

        // Act
        $result = $this->user->fresh()->isPayingPatron();

        // Assert
        $this->assertFalse($result);
    }

    #[Test]
    public function isPayingPatron_givenAdminWithoutLink_returnsFalse(): void
    {
        // Arrange
        $this->user = User::factory()->create();
        $this->user->addRole(Role::firstWhere('name', Role::ROLE_ADMIN));

        // Act
        $result = $this->user->fresh()->isPayingPatron();

        // Assert
        $this->assertFalse($result);
    }

    private function createPatreonUserLink(User $user, bool $withBenefit, bool $manuallyGranted = false): PatreonUserLink
    {
        $factory = PatreonUserLink::factory();
        if ($manuallyGranted) {
            $factory = $factory->manuallyGranted();
        }

        $patreonUserLink = $factory->create(['user_id' => $user->id]);
        $user->update(['patreon_user_link_id' => $patreonUserLink->id]);

        if ($withBenefit) {
            PatreonUserBenefit::create([
                'patreon_user_link_id' => $patreonUserLink->id,
                'patreon_benefit_id'   => PatreonBenefit::ALL[PatreonBenefit::AD_FREE],
            ]);
        }

        return $patreonUserLink;
    }
}
