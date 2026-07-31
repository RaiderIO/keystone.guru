<?php

namespace Tests\Feature\App\Models\Feature;

use App\Features\NpcCompendium;
use App\Models\Feature\Feature;
use App\Models\Laratrust\Role;
use App\Models\User;
use Laravel\Pennant\Feature as PennantFeature;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('Feature')]
final class FeatureForgetAllForUserTest extends PublicTestCase
{
    /**
     * A name that matches no class in App\Features, so that a test which needs a stored value for the admin user
     * never touches (or restores incorrectly) the real rows that drive the global on/off switch of every feature.
     */
    private const string SYNTHETIC_FEATURE_NAME = 'Tests\\Feature\\App\\Models\\Feature\\SyntheticFeature';

    protected function setUp(): void
    {
        parent::setUp();

        // The suite runs Pennant on its array store (see phpunit.xml), but what's under test here is precisely
        // the rows in the features table - so these tests need the database store the application really runs on
        config(['pennant.default' => 'database']);
        PennantFeature::forgetDrivers();
    }

    protected function tearDown(): void
    {
        PennantFeature::forgetDrivers();

        parent::tearDown();
    }

    #[Test]
    public function addRole_givenUserWithStoredFeatureValues_forgetsThoseValues(): void
    {
        // Arrange - the user browsed the site before being given the role, so their 'false' is already stored
        $user = User::factory()->create();

        try {
            PennantFeature::for($user)->deactivate(NpcCompendium::class);
            $this->assertSame(1, $this->countStoredFeaturesOf($user), 'Expected the stored value to be arranged.');

            // Act
            $user->addRole(Role::ROLE_INTERNAL_TEAM);

            // Assert
            $this->assertSame(0, $this->countStoredFeaturesOf($user));
        } finally {
            $this->deleteStoredFeaturesOf($user);
            $user->delete();
        }
    }

    #[Test]
    public function removeRole_givenUserWithStoredFeatureValues_forgetsThoseValues(): void
    {
        // Arrange
        $user = User::factory()->create();

        try {
            $user->addRole(Role::ROLE_INTERNAL_TEAM);
            PennantFeature::for($user)->activate(NpcCompendium::class);
            $this->assertSame(1, $this->countStoredFeaturesOf($user), 'Expected the stored value to be arranged.');

            // Act
            $user->removeRole(Role::ROLE_INTERNAL_TEAM);

            // Assert
            $this->assertSame(0, $this->countStoredFeaturesOf($user));
        } finally {
            $this->deleteStoredFeaturesOf($user);
            $user->delete();
        }
    }

    #[Test]
    public function removeRoles_givenNoRolesToRemove_forgetsStoredFeatureValues(): void
    {
        // Arrange - removeRoles() only loops removeRole() when it's handed roles; an empty array syncs instead,
        // which fires role.synced rather than role.removed
        $user = User::factory()->create();

        try {
            $user->addRole(Role::ROLE_INTERNAL_TEAM);
            PennantFeature::for($user)->activate(NpcCompendium::class);
            $this->assertSame(1, $this->countStoredFeaturesOf($user), 'Expected the stored value to be arranged.');

            // Act
            $user->removeRoles();

            // Assert
            $this->assertSame(0, $this->countStoredFeaturesOf($user));
        } finally {
            $this->deleteStoredFeaturesOf($user);
            $user->delete();
        }
    }

    #[Test]
    public function forgetAllForUser_givenAdminUser_keepsStoredFeatureValues(): void
    {
        // Arrange - the admin's stored values are the global on/off switch of every feature, so dropping them
        // would disable the lot site-wide. Asserting on a synthetic name keeps the real switches out of it.
        $admin      = User::findOrFail(Feature::ADMIN_USER_ID);
        $adminScope = $this->serializeScopeOf($admin);
        $this->assertTrue($admin->hasRole(Role::ROLE_ADMIN), 'User id=1 must be admin (seed the DB).');

        $storedValuesOfAdmin = Feature::query()->where('scope', $adminScope)->get();

        try {
            Feature::query()->insert([
                'name'       => self::SYNTHETIC_FEATURE_NAME,
                'scope'      => $adminScope,
                'value'      => 'true',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Act
            Feature::forgetAllForUser($admin);

            // Assert
            $this->assertSame(1, Feature::query()
                ->where('name', self::SYNTHETIC_FEATURE_NAME)
                ->where('scope', $adminScope)
                ->count());
        } finally {
            Feature::query()->where('name', self::SYNTHETIC_FEATURE_NAME)->delete();

            // The Act purges every stored name, not just the synthetic one - so should the guard under test ever
            // regress, put the admin's real rows back rather than leaving the shared test database (which has no
            // RefreshDatabase to save it) without the global on/off switch of every feature
            foreach ($storedValuesOfAdmin as $storedValueOfAdmin) {
                Feature::query()->updateOrInsert([
                    'name'  => $storedValueOfAdmin->name,
                    'scope' => $adminScope,
                ], [
                    'value'      => $storedValueOfAdmin->value,
                    'created_at' => $storedValueOfAdmin->created_at,
                    'updated_at' => $storedValueOfAdmin->updated_at,
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
