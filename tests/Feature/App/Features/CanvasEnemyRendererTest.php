<?php

namespace Tests\Feature\App\Features;

use App\Features\CanvasEnemyRenderer;
use App\Models\Feature\Feature;
use App\Models\Laratrust\Role;
use App\Models\User;
use Laravel\Pennant\Feature as Pennant;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('Feature')]
#[Group('CanvasEnemyRenderer')]
final class CanvasEnemyRendererTest extends PublicTestCase
{
    private ?string $originalAdminValue = null;

    private bool $adminRowExisted = false;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $adminRow                 = $this->findAdminRow();
        $this->adminRowExisted    = $adminRow !== null;
        $this->originalAdminValue = $adminRow?->value;
    }

    #[\Override]
    protected function tearDown(): void
    {
        try {
            if ($this->adminRowExisted) {
                $this->setAdminValue($this->originalAdminValue === 'true');
            } else {
                $this->findAdminRow()?->delete();
            }
        } finally {
            parent::tearDown();
        }
    }

    #[Test]
    public function resolve_givenAdminSwitchOff_returnsFalseEvenForInternalTeam(): void
    {
        // Arrange
        $this->setAdminValue(false);
        $user = User::factory()->create();
        $user->addRole(Role::ROLE_INTERNAL_TEAM);

        try {
            // Act
            $result = (new CanvasEnemyRenderer())->resolve($user);

            // Assert
            $this->assertFalse($result);
        } finally {
            $user->delete();
        }
    }

    #[Test]
    public function resolve_givenAdminSwitchOnAndInternalTeam_returnsTrue(): void
    {
        // Arrange
        $this->setAdminValue(true);
        $user = User::factory()->create();
        $user->addRole(Role::ROLE_INTERNAL_TEAM);

        try {
            // Act
            $result = (new CanvasEnemyRenderer())->resolve($user);

            // Assert
            $this->assertTrue($result);
        } finally {
            $user->delete();
        }
    }

    #[Test]
    public function resolve_givenAdminSwitchOnAndRegularUser_returnsFalse(): void
    {
        // Arrange
        $this->setAdminValue(true);
        $user = User::factory()->create();

        try {
            // Act
            $result = (new CanvasEnemyRenderer())->resolve($user);

            // Assert
            $this->assertFalse($result);
        } finally {
            $user->delete();
        }
    }

    #[Test]
    public function resolve_givenAdminSwitchOnAndGuest_returnsFalse(): void
    {
        // Arrange
        $this->setAdminValue(true);

        // Act
        $result = (new CanvasEnemyRenderer())->resolve(null);

        // Assert
        $this->assertFalse($result);
    }

    #[Test]
    #[DataProvider('mapPageProvider')]
    public function isEnabledForMap_givenFeatureActive_enablesOnlyReadOnlyPagesThatOptedIn(
        bool $pageAllowsCanvas,
        bool $isEditPage,
        bool $isMappingEditor,
        bool $expected,
    ): void {
        // Arrange
        Pennant::for(null)->activate(CanvasEnemyRenderer::class);

        try {
            // Act
            $result = CanvasEnemyRenderer::isEnabledForMap($pageAllowsCanvas, $isEditPage, $isMappingEditor);

            // Assert
            $this->assertSame($expected, $result);
        } finally {
            Pennant::for(null)->forget(CanvasEnemyRenderer::class);
        }
    }

    /**
     * @return array<string, array{0: bool, 1: bool, 2: bool, 3: bool}>
     */
    public static function mapPageProvider(): array
    {
        return [
            'read-only page that opted in'    => [true, false, false, true],
            'page that did not opt in'        => [false, false, false, false],
            'edit page that opted in'         => [true, true, false, false],
            'mapping editor that opted in'    => [true, false, true, false],
            'edit page in the mapping editor' => [true, true, true, false],
        ];
    }

    #[Test]
    public function isEnabledForMap_givenFeatureInactive_returnsFalse(): void
    {
        // Arrange
        Pennant::for(null)->deactivate(CanvasEnemyRenderer::class);

        try {
            // Act
            $result = CanvasEnemyRenderer::isEnabledForMap(true, false, false);

            // Assert
            $this->assertFalse($result);
        } finally {
            Pennant::for(null)->forget(CanvasEnemyRenderer::class);
        }
    }

    private function findAdminRow(): ?Feature
    {
        return Feature::query()
            ->where('name', CanvasEnemyRenderer::class)
            ->where('scope', sprintf('%s|%d', User::class, Feature::ADMIN_USER_ID))
            ->first();
    }

    private function setAdminValue(bool $active): void
    {
        $adminRow        = $this->findAdminRow() ?? new Feature();
        $adminRow->name  = CanvasEnemyRenderer::class;
        $adminRow->scope = sprintf('%s|%d', User::class, Feature::ADMIN_USER_ID);
        $adminRow->value = $active ? 'true' : 'false';
        $adminRow->save();
    }
}
