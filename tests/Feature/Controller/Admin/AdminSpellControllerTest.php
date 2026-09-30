<?php

namespace Tests\Feature\Controller\Admin;

use App\Models\Mapping\MappingChangeLog;
use App\Models\Spell\Spell;
use App\Models\Spell\SpellSchool;
use App\Models\User;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fixtures\Traits\CreatesSpell;
use Tests\TestCases\PublicTestCase;

#[Group('Controller')]
#[Group('Admin')]
#[Group('Spell')]
final class AdminSpellControllerTest extends PublicTestCase
{
    use CreatesSpell;

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
        // SpellDispelType::translationKeys() (prefixed) as the dropdown's option values, so that is what a
        // real submission sends back. A regression here (e.g. dropping the prefix again, or
        // re-introducing a mismatched unprefixed option list) must fail this test.
        $spell = $this->createSpell();

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

    #[Test]
    public function update_givenUnprefixedDispelType_returnsValidationError(): void
    {
        // Arrange
        $spell = $this->createSpell(['dispel_type' => 'spelldispeltype.magic']);

        // Act
        $response = $this->patch(route('admin.spell.update', $spell), [
            'id'             => $spell->id,
            'name'           => $spell->name,
            'icon_name'      => $spell->icon_name,
            'category'       => 'general',
            'dispel_type'    => 'disease',
            'cooldown_group' => 'all',
            'submit'         => 'Submit',
        ]);

        // Assert
        $response->assertSessionHasErrors('dispel_type');
        $this->assertSame('spelldispeltype.magic', Spell::query()->findOrFail($spell->id)->dispel_type);
    }

    #[Test]
    public function update_givenSchools_persistsTheirCombinedMask(): void
    {
        // Arrange
        $spell = $this->createSpell(['schools_mask' => SpellSchool::Physical->value]);

        try {
            // Act
            $response = $this->patch(route('admin.spell.update', $spell), [
                'id'             => $spell->id,
                'name'           => $spell->name,
                'icon_name'      => $spell->icon_name,
                'category'       => 'general',
                'dispel_type'    => 'spelldispeltype.disease',
                'cooldown_group' => 'all',
                'schools'        => [SpellSchool::Holy->value, SpellSchool::Fire->value],
                'submit'         => 'Submit',
            ]);

            // Assert
            $response->assertOk();
            $this->assertSame(
                SpellSchool::Holy->value | SpellSchool::Fire->value,
                Spell::query()->findOrFail($spell->id)->schools_mask,
            );
        } finally {
            MappingChangeLog::query()->where('model_class', Spell::class)->where('model_id', $spell->id)->delete();
        }
    }
}
