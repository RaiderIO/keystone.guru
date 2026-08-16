<?php

namespace Tests\Feature\Controller\AdminTools;

use App\Features\NpcCompendium;
use App\Models\Feature\Feature;
use App\Models\Laratrust\Role;
use App\Models\User;
use Laravel\Pennant\Feature as PennantFeature;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('Controller')]
#[Group('AdminTools')]
final class AdminToolsFeaturesControllerTest extends PublicTestCase
{
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
    public function toggleFeature_givenOrdinaryUserWithStoredFalseValue_grantsTheFeatureToThatUserOnceSwitchedOn(): void
    {
        // Arrange - the admin switch starts off, and an ordinary user already has a stored 'false' from browsing
        // the site before the toggle. The feature has no role gate anymore, so once the admin switch flips on,
        // that stale 'false' must be purged rather than left to shadow the now-enabled switch
        $admin        = User::findOrFail(Feature::ADMIN_USER_ID);
        $ordinaryUser = User::factory()->create();
        $this->assertTrue($admin->hasRole(Role::ROLE_ADMIN), 'User id=1 must be admin (seed the DB).');
        $adminBackup = Feature::query()->where('scope', $this->serializeScopeOf($admin))
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
                'Expected the stale stored row to be purged, not left in place.',
            );
            $this->assertTrue(
                PennantFeature::for($ordinaryUser)->active(NpcCompendium::class),
                'Expected the feature to resolve to true for any user once the admin switch is on.',
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
        // Arrange - a user's stored 'true' row must be purged, not merely updated to 'false' in place; updating it
        // in place would leave a row that never re-resolves once the switch is turned back on, which would
        // recreate the exact stale-value bug #3772 fixed for the role-change direction
        $admin        = User::findOrFail(Feature::ADMIN_USER_ID);
        $ordinaryUser = User::factory()->create();
        $this->assertTrue($admin->hasRole(Role::ROLE_ADMIN), 'User id=1 must be admin (seed the DB).');
        $adminBackup = Feature::query()->where('scope', $this->serializeScopeOf($admin))
            ->where('name', NpcCompendium::class)
            ->first();

        try {
            PennantFeature::for($admin)->activate(NpcCompendium::class);
            PennantFeature::for($ordinaryUser)->activate(NpcCompendium::class);
            $this->assertTrue(Feature::getAdminValue(NpcCompendium::class), 'Expected the switch to be arranged on.');
            $this->assertSame(1, $this->countStoredFeaturesOf($ordinaryUser), 'Expected the stored value to be arranged.');

            // Act
            $this->be($admin);
            $response = $this->post(route('admin.tools.features.toggle'), ['feature' => NpcCompendium::class]);

            // Assert
            $response->assertRedirect(route('admin.tools.features.list'));
            $this->assertFalse(Feature::getAdminValue(NpcCompendium::class), 'Expected the switch to be flipped off.');
            $this->assertSame(
                0,
                $this->countStoredFeaturesOf($ordinaryUser),
                'Expected the stored row to be purged, not updated to false in place.',
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
