<?php

namespace Tests\Feature\Controller\Admin;

use App\Models\Expansion;
use App\Models\User;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fixtures\Traits\CreatesExpansion;
use Tests\TestCases\PublicTestCase;

#[Group('Controller')]
#[Group('Admin')]
final class AdminExpansionControllerTest extends PublicTestCase
{
    use CreatesExpansion;

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
            $created = Expansion::query()->where('shortname', $shortname)->first();
            $this->assertNotNull($created, 'Expansion should be created without requiring an icon upload');
            $this->assertSame('Test Expansion', $created->name);
            $this->assertSame('#ffffff', $created->color);
            $this->assertEquals(1, $created->active);
        } finally {
            Expansion::query()->where('shortname', $shortname)->delete();
        }
    }

    #[Test]
    public function savenew_givenShortnameOfExistingExpansion_returnsValidationError(): void
    {
        // Arrange
        $existing = Expansion::query()->firstOrFail();
        $name     = 'Test Duplicate Shortname Expansion';

        // Act
        $response = $this->post(route('admin.expansion.savenew'), [
            'active'    => 1,
            'name'      => $name,
            'shortname' => $existing->shortname,
            'color'     => '#ffffff',
        ]);

        // Assert
        $response->assertSessionHasErrors('shortname');
        $this->assertFalse(Expansion::query()->where('name', $name)->exists());
    }

    #[Test]
    public function get_asAdmin_returnsAllExpansions(): void
    {
        // Arrange

        // Act
        $response = $this->get(route('admin.expansions'));

        // Assert
        $response->assertOk();
        $response->assertViewHas('expansions', fn($expansions) => $expansions->count() === Expansion::query()->count());
    }

    #[Test]
    public function update_givenValidData_updatesExpansion(): void
    {
        // Arrange
        $expansion = $this->createExpansion(['active' => true]);

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
    }
}
