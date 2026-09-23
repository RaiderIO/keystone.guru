<?php

namespace Tests\Feature\App\Features;

use App\Features\CanvasEnemyRenderer;
use App\Models\Feature\Feature;
use App\Models\Laratrust\Role;
use App\Models\User;
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
