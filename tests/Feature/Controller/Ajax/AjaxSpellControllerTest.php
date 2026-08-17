<?php

namespace Tests\Feature\Controller\Ajax;

use App\Models\Spell\Spell;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\AjaxPublicTestCase;

#[Group('Controller')]
#[Group('Spell')]
final class AjaxSpellControllerTest extends AjaxPublicTestCase
{
    #[Test]
    public function update_givenPrefixedDispelType_persistsItUnchanged(): void
    {
        // Arrange - #4095: AjaxSpellUpdateFormRequest validates dispel_type against
        // Spell::ALL_DISPEL_TYPE_KEYS (prefixed), so this is the shape a real request sends.
        $spell = Spell::query()->firstOrFail();

        // Act
        $response = $this->put(sprintf('/ajax/admin/spell/%s', $spell->getRouteKey()), [
            'dispel_type' => 'spelldispeltype.curse',
        ]);

        // Assert
        $response->assertOk();
        $this->assertSame('spelldispeltype.curse', Spell::query()->findOrFail($spell->id)->dispel_type);
    }
}
