<?php

namespace Tests\Feature\Controller\Admin;

use App\Models\Spell\Spell;
use App\Models\User;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('Controller')]
#[Group('Admin')]
#[Group('Spell')]
final class AdminSpellControllerTest extends PublicTestCase
{
    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->be(User::findOrFail(1));
    }

    #[Test]
    public function update_givenDispelTypeSubmittedFromTheEditForm_persistsThePrefixedTranslationKey(): void
    {
        // Arrange - #4095: SpellController::getEditViewParams() hands the edit form
        // Spell::ALL_DISPEL_TYPE_KEYS (prefixed) as the dropdown's option values, so that is what a
        // real submission sends back. A regression here (e.g. dropping the prefix again, or
        // re-introducing a mismatched unprefixed option list) must fail this test.
        $spell = Spell::query()->firstOrFail();

        // Act
        $response = $this->patch(route('admin.spell.update', $spell), [
            'id'             => $spell->id,
            'name'           => $spell->name,
            'icon_name'      => $spell->icon_name,
            'category'       => 'general',
            'dispel_type'    => 'spelldispeltype.disease',
            'cooldown_group' => 'all',
            'submit'         => 'Submit',
        ]);

        // Assert
        $response->assertOk();
        $this->assertSame('spelldispeltype.disease', Spell::query()->findOrFail($spell->id)->dispel_type);
    }
}
