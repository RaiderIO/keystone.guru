<?php

namespace Tests\Feature\Controller\Admin;

use App\Models\Expansion;
use App\Models\User;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('Controller')]
#[Group('Admin')]
final class AdminExpansionControllerTest extends PublicTestCase
{
    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->be(User::findOrFail(1));
    }

    #[Test]
    public function edit_givenExistingExpansion_returnsOkWithAssetIcon(): void
    {
        // Arrange
        $expansion = Expansion::query()->firstOrFail();

        // Act
        $response = $this->get(route('admin.expansion.edit', $expansion));

        // Assert
        $response->assertOk();
        $response->assertSee($expansion->getIconUrl());
    }

    #[Test]
    public function create_givenNoExpansion_returnsOk(): void
    {
        // Arrange

        // Act
        $response = $this->get(route('admin.expansion.new'));

        // Assert
        $response->assertOk();
    }

    #[Test]
    public function savenew_givenValidDataWithoutIcon_createsExpansion(): void
    {
        // Arrange
        $shortname = 'testexp';

        try {
            // Act
            $response = $this->post(route('admin.expansion.savenew'), [
                'active'    => 1,
                'name'      => 'Test Expansion',
                'shortname' => $shortname,
                'color'     => '#ffffff',
            ]);

            // Assert
            $response->assertRedirect(route('admin.expansion.edit', $shortname));
            $this->assertTrue(
                Expansion::query()->where('shortname', $shortname)->exists(),
                'Expansion should be created without requiring an icon upload',
            );
        } finally {
            Expansion::query()->where('shortname', $shortname)->delete();
        }
    }

    #[Test]
    public function update_givenValidData_updatesExpansion(): void
    {
        // Arrange
        $expansion      = Expansion::query()->firstOrFail();
        $originalName   = $expansion->name;
        $originalActive = $expansion->active;
        $originalColor  = $expansion->color;

        try {
            // Act
            $response = $this->patch(route('admin.expansion.update', $expansion), [
                'active'    => 0,
                'name'      => 'Updated Expansion',
                'shortname' => $expansion->shortname,
                'color'     => '#123456',
            ]);

            // Assert
            $response->assertOk();

            $updated = Expansion::query()->findOrFail($expansion->id);
            $this->assertSame('Updated Expansion', $updated->name);
            $this->assertEquals(0, $updated->active);
            $this->assertSame('#123456', $updated->color);
        } finally {
            Expansion::query()->where('id', $expansion->id)->update([
                'name'   => $originalName,
                'active' => $originalActive,
                'color'  => $originalColor,
            ]);
        }
    }
}
