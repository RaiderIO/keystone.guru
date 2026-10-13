<?php

namespace Tests\Feature\Controller\Ajax;

use App\Models\Spell\Spell;
use App\Models\User;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fixtures\Traits\CreatesSpell;
use Tests\TestCases\AjaxPublicTestCase;

#[Group('Controller')]
#[Group('Spell')]
final class AjaxSpellControllerTest extends AjaxPublicTestCase
{
    use CreatesSpell;

    #[Test]
    public function update_givenPrefixedDispelType_persistsItUnchanged(): void
    {
        // Arrange - #4095: AjaxSpellUpdateFormRequest validates dispel_type against
        // SpellDispelType::translationKeys() (prefixed), so this is the shape a real request sends.
        $spell = $this->createSpell(['dispel_type' => 'spelldispeltype.magic']);

        // Act
        $response = $this->put(sprintf('/ajax/admin/spell/%s', $spell->getRouteKey()), [
            'dispel_type' => 'spelldispeltype.curse',
        ]);

        // Assert
        $response->assertOk();
        $this->assertSame('spelldispeltype.curse', Spell::query()->findOrFail($spell->id)->dispel_type);
    }

    #[Test]
    public function update_givenUnknownDispelType_returnsValidationErrorAndKeepsTheSpell(): void
    {
        // Arrange
        $spell = $this->createSpell(['dispel_type' => 'spelldispeltype.magic']);

        // Act
        $response = $this->putJson(sprintf('/ajax/admin/spell/%s', $spell->getRouteKey()), [
            'dispel_type' => 'curse',
        ]);

        // Assert
        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['dispel_type']);
        $this->assertSame('spelldispeltype.magic', Spell::query()->findOrFail($spell->id)->dispel_type);
    }

    #[Test]
    public function update_givenAnEmptyGameVersion_returnsValidationErrorAndKeepsTheGameVersion(): void
    {
        // Arrange
        $spell = $this->createSpell();

        // Act
        $response = $this->putJson(sprintf('/ajax/admin/spell/%s', $spell->getRouteKey()), [
            'game_version_id' => '',
        ]);

        // Assert
        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['game_version_id']);
        $this->assertSame($spell->game_version_id, Spell::query()->findOrFail($spell->id)->game_version_id);
    }

    #[Test]
    public function update_givenNonAdmin_returnsForbiddenAndKeepsTheSpell(): void
    {
        // Arrange
        $spell    = $this->createSpell(['dispel_type' => 'spelldispeltype.magic']);
        $nonAdmin = User::factory()->create();

        try {
            $this->actingAs($nonAdmin);

            // Act
            $response = $this->put(sprintf('/ajax/admin/spell/%s', $spell->getRouteKey()), [
                'dispel_type' => 'spelldispeltype.curse',
            ]);

            // Assert
            $response->assertForbidden();
            $this->assertSame('spelldispeltype.magic', Spell::query()->findOrFail($spell->id)->dispel_type);
        } finally {
            $nonAdmin->delete();
        }
    }
}
