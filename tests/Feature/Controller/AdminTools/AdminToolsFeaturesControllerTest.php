<?php

namespace Tests\Feature\Controller\AdminTools;

use App\Features\NpcCompendium;
use App\Models\Feature\Feature;
use App\Models\User;
use Laravel\Pennant\Feature as PennantFeature;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('Controller')]
#[Group('AdminTools')]
final class AdminToolsFeaturesControllerTest extends PublicTestCase
{
    private const int ADMIN_USER_ID = 1;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        // The suite runs Pennant on its array store (see phpunit.xml), but what's under test here is precisely
        // the rows in the features table - so these tests need the database store the application really runs on
        config(['pennant.default' => 'database']);
        PennantFeature::forgetDrivers();
    }

    #[\Override]
    protected function tearDown(): void
    {
        PennantFeature::forgetDrivers();

        parent::tearDown();
    }

    #[Test]
    public function toggleFeature_givenOrdinaryUserWithStoredFalseValue_doesNotGrantTheFeatureToThatUser(): void
    {
        // Arrange - the admin switch starts off, and an ordinary (non-internal-team) user already has a stored
        // 'false' from browsing the site before the toggle, which is exactly the row #3774 was blanket-flipped
        $admin        = User::findOrFail(self::ADMIN_USER_ID);
        $ordinaryUser = User::factory()->create();
        $adminBackup  = Feature::query()->where('scope', $this->serializeScopeOf($admin))
            ->where('name', NpcCompendium::class)
            ->first();

        try {
            PennantFeature::for($admin)->deactivate(NpcCompendium::class);
            PennantFeature::for($ordinaryUser)->deactivate(NpcCompendium::class);
            $this->assertFalse(Feature::getAdminValue(NpcCompendium::class), 'Expected the switch to be arranged off.');

            // Act
            $this->be($admin);
            $response = $this->post(route('admin.tools.features.toggle'), ['feature' => NpcCompendium::class]);

            // Assert
            $response->assertRedirect(route('admin.tools.features.list'));
            $this->assertTrue(Feature::getAdminValue(NpcCompendium::class), 'Expected the switch to be flipped on.');

            $this->assertSame(
                0,
                $this->countStoredFeaturesOf($ordinaryUser),
                'Expected the stored row of a user with no entitling role to be purged, not flipped to true.',
            );
            $this->assertFalse(
                PennantFeature::for($ordinaryUser)->active(NpcCompendium::class),
                'Expected the feature to resolve to false for a user without the entitling role.',
            );
        } finally {
            $this->deleteStoredFeaturesOf($ordinaryUser);
            $ordinaryUser->delete();

            Feature::query()->where('scope', $this->serializeScopeOf($admin))
                ->where('name', NpcCompendium::class)
                ->delete();
            if ($adminBackup !== null) {
                Feature::query()->insert([
                    'name'       => $adminBackup->name,
                    'scope'      => $adminBackup->scope,
                    'value'      => $adminBackup->value,
                    'created_at' => $adminBackup->created_at,
                    'updated_at' => $adminBackup->updated_at,
                ]);
            }
        }
    }

    #[Test]
    public function toggleFeature_givenActiveSwitch_deactivatesItAndPurgesStoredValues(): void
    {
        // Arrange
        $admin       = User::findOrFail(self::ADMIN_USER_ID);
        $adminBackup = Feature::query()->where('scope', $this->serializeScopeOf($admin))
            ->where('name', NpcCompendium::class)
            ->first();

        try {
            PennantFeature::for($admin)->activate(NpcCompendium::class);
            $this->assertTrue(Feature::getAdminValue(NpcCompendium::class), 'Expected the switch to be arranged on.');

            // Act
            $this->be($admin);
            $response = $this->post(route('admin.tools.features.toggle'), ['feature' => NpcCompendium::class]);

            // Assert
            $response->assertRedirect(route('admin.tools.features.list'));
            $this->assertFalse(Feature::getAdminValue(NpcCompendium::class), 'Expected the switch to be flipped off.');
        } finally {
            Feature::query()->where('scope', $this->serializeScopeOf($admin))
                ->where('name', NpcCompendium::class)
                ->delete();
            if ($adminBackup !== null) {
                Feature::query()->insert([
                    'name'       => $adminBackup->name,
                    'scope'      => $adminBackup->scope,
                    'value'      => $adminBackup->value,
                    'created_at' => $adminBackup->created_at,
                    'updated_at' => $adminBackup->updated_at,
                ]);
            }
        }
    }

    private function countStoredFeaturesOf(User $user): int
    {
        return Feature::query()->where('scope', $this->serializeScopeOf($user))->count();
    }

    private function deleteStoredFeaturesOf(User $user): void
    {
        Feature::query()->where('scope', $this->serializeScopeOf($user))->delete();
    }

    private function serializeScopeOf(User $user): string
    {
        return sprintf('%s|%d', User::class, $user->id);
    }
}
