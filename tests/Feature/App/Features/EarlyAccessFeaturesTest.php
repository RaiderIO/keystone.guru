<?php

namespace Tests\Feature\App\Features;

use App\Features\CanvasEnemyRenderer;
use App\Features\CreatorProfiles;
use App\Features\XalatathTheme;
use App\Models\Feature\Feature;
use App\Models\Laratrust\Role;
use App\Models\User;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('Feature')]
#[Group('BetaTesterRole')]
final class EarlyAccessFeaturesTest extends PublicTestCase
{
    #[Test]
    #[DataProvider('featureRoleProvider')]
    public function resolve_givenAdminSwitchOn_resolvesPerRole(string $featureClass, ?string $role, bool $expected): void
    {
        // Arrange
        $adminRow           = $this->findAdminRow($featureClass);
        $adminRowExisted    = $adminRow !== null;
        $originalAdminValue = $adminRow?->value;
        $this->setAdminValue($featureClass, true);
        $user = User::factory()->create();

        try {
            if ($role !== null) {
                $user->addRole($role);
            }

            // Act
            $result = (new $featureClass())->resolve($user);

            // Assert
            $this->assertSame($expected, $result);
        } finally {
            $user->delete();
            if ($adminRowExisted) {
                $this->setAdminValue($featureClass, $originalAdminValue === 'true');
            } else {
                $this->findAdminRow($featureClass)?->delete();
            }
        }
    }

    /**
     * @return array<string, array{0: class-string, 1: string|null, 2: bool}>
     */
    public static function featureRoleProvider(): array
    {
        $result = [];
        foreach ([CanvasEnemyRenderer::class, CreatorProfiles::class, XalatathTheme::class] as $featureClass) {
            $shortName                                        = class_basename($featureClass);
            $result[sprintf('%s, beta tester', $shortName)]   = [$featureClass, Role::ROLE_BETA_TESTER, true];
            $result[sprintf('%s, internal team', $shortName)] = [$featureClass, Role::ROLE_INTERNAL_TEAM, true];
            $result[sprintf('%s, no role', $shortName)]       = [$featureClass, null, false];
        }

        return $result;
    }

    private function findAdminRow(string $featureClass): ?Feature
    {
        return Feature::query()
            ->where('name', $featureClass)
            ->where('scope', sprintf('%s|%d', User::class, Feature::ADMIN_USER_ID))
            ->first();
    }

    private function setAdminValue(string $featureClass, bool $active): void
    {
        $adminRow        = $this->findAdminRow($featureClass) ?? new Feature();
        $adminRow->name  = $featureClass;
        $adminRow->scope = sprintf('%s|%d', User::class, Feature::ADMIN_USER_ID);
        $adminRow->value = $active ? 'true' : 'false';
        $adminRow->save();
    }
}
