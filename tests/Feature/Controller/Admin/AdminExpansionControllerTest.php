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
    public function edit_givenKeyWithLegacyShortname_bindsExpansionByKey(): void
    {
        // Arrange
        $expansion = $this->createExpansion();
        Expansion::query()->whereKey($expansion->id)->update(['shortname' => sprintf('legacy_%s', $expansion->key)]);

        // Act
        $response = $this->get(route('admin.expansion.edit', $expansion->key));

        // Assert
        $response->assertOk();
        $response->assertViewHas('expansion', fn(Expansion $bound) => $bound->id === $expansion->id);
    }

    #[Test]
    public function edit_givenLegacyShortnameThatIsNoKey_returnsNotFound(): void
    {
        // Arrange
        $expansion       = $this->createExpansion();
        $legacyShortname = sprintf('legacy_%s', $expansion->key);
        Expansion::query()->whereKey($expansion->id)->update(['shortname' => $legacyShortname]);

        // Act
        $response = $this->get(route('admin.expansion.edit', $legacyShortname));

        // Assert
        $response->assertNotFound();
    }

    #[Test]
    public function savenew_givenValidDataWithoutIcon_createsExpansionWithKeyAndShortname(): void
    {
        // Arrange
        $key = 'testexp';

        try {
            // Act
            $response = $this->post(route('admin.expansion.savenew'), [
                'active' => 1,
                'name'   => 'Test Expansion',
                'key'    => $key,
                'color'  => '#ffffff',
            ]);

            // Assert
            $response->assertRedirect(route('admin.expansion.edit', $key));
            $created = Expansion::query()->where('key', $key)->first();
            $this->assertNotNull($created, 'Expansion should be created without requiring an icon upload');
            $this->assertSame('Test Expansion', $created->name);
            $this->assertSame($key, $created->shortname);
            $this->assertSame('#ffffff', $created->color);
            $this->assertEquals(1, $created->active);
        } finally {
            Expansion::query()->where('key', $key)->delete();
        }
    }

    #[Test]
    public function savenew_givenKeyOfExistingExpansion_returnsValidationError(): void
    {
        // Arrange
        $existing = Expansion::query()->firstOrFail();
        $name     = 'Test Duplicate Key Expansion';

        // Act
        $response = $this->post(route('admin.expansion.savenew'), [
            'active' => 1,
            'name'   => $name,
            'key'    => $existing->key,
            'color'  => '#ffffff',
        ]);

        // Assert
        $response->assertSessionHasErrors('key');
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
    public function update_givenValidDataWithNewKey_updatesExpansionKeyAndShortname(): void
    {
        // Arrange
        $expansion = $this->createExpansion(['active' => true]);
        $newKey    = sprintf('%s_renamed', $expansion->key);

        // Act
        $response = $this->patch(route('admin.expansion.update', $expansion), [
            'active' => 0,
            'name'   => 'Updated Expansion',
            'key'    => $newKey,
            'color'  => '#123456',
        ]);

        // Assert
        $response->assertOk();

        $updated = Expansion::query()->findOrFail($expansion->id);
        $this->assertSame('Updated Expansion', $updated->name);
        $this->assertSame($newKey, $updated->key);
        $this->assertSame($newKey, $updated->shortname);
        $this->assertEquals(0, $updated->active);
        $this->assertSame('#123456', $updated->color);
    }

    #[Test]
    public function update_givenMissingKey_returnsValidationErrorAndKeepsExpansion(): void
    {
        // Arrange
        $expansion = $this->createExpansion();

        // Act
        $response = $this->patch(route('admin.expansion.update', $expansion), [
            'active' => 0,
            'name'   => 'Updated Expansion',
            'color'  => '#123456',
        ]);

        // Assert
        $response->assertSessionHasErrors('key');
        $unchanged = Expansion::query()->findOrFail($expansion->id);
        $this->assertSame($expansion->key, $unchanged->key);
        $this->assertSame('Test Expansion', $unchanged->name);
    }

    #[Test]
    public function update_givenKeyOfAnotherExpansion_returnsValidationErrorAndKeepsExpansion(): void
    {
        // Arrange
        $expansion = $this->createExpansion();
        $other     = $this->createExpansion();

        // Act
        $response = $this->patch(route('admin.expansion.update', $expansion), [
            'active' => 0,
            'name'   => 'Updated Expansion',
            'key'    => $other->key,
            'color'  => '#123456',
        ]);

        // Assert
        $response->assertSessionHasErrors('key');
        $unchanged = Expansion::query()->findOrFail($expansion->id);
        $this->assertSame($expansion->key, $unchanged->key);
        $this->assertSame($expansion->key, $unchanged->shortname);
    }
}
