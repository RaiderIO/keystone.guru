<?php

namespace Tests\Feature\View\Common;

use App\Models\Spell\Spell;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('SpellDescription')]
final class SpellLinkTest extends PublicTestCase
{
    private const int SPELL_ID = 999999801;

    #[Test]
    public function render_givenSpellWithDescription_returnsOurOwnTooltip(): void
    {
        // Arrange
        $spell = null;

        try {
            $spell = $this->createSpell('Slams the ground for 8 sec.');

            // Act
            $result = view('common.spell.link', ['spell' => $spell])->render();

            // Assert
            $this->assertStringContainsString('data-spell-description="Slams the ground for 8 sec."', $result);
            $this->assertStringNotContainsString('data-wowhead', $result);
        } finally {
            $spell?->delete();
            new Spell()->flushCache();
        }
    }

    #[Test]
    public function render_givenSpellWithoutDescription_fallsBackToWowhead(): void
    {
        // Arrange
        $spell = null;

        try {
            $spell = $this->createSpell(null);

            // Act
            $result = view('common.spell.link', ['spell' => $spell])->render();

            // Assert
            $this->assertStringContainsString(sprintf('data-wowhead="spell=%d"', self::SPELL_ID), $result);
            $this->assertStringNotContainsString('data-spell-description', $result);
        } finally {
            $spell?->delete();
            new Spell()->flushCache();
        }
    }

    #[Test]
    public function render_givenDescriptionWithMarkup_escapesIt(): void
    {
        // Arrange - descriptions come from an external data source and must never render as HTML
        $spell = null;

        try {
            $spell = $this->createSpell('<script>alert(1)</script>');

            // Act
            $result = view('common.spell.link', ['spell' => $spell])->render();

            // Assert
            $this->assertStringNotContainsString('<script>', $result);
            $this->assertStringContainsString('&lt;script&gt;', $result);
        } finally {
            $spell?->delete();
            new Spell()->flushCache();
        }
    }

    private function createSpell(?string $description): Spell
    {
        return Spell::create([
            'id'              => self::SPELL_ID,
            'game_version_id' => 1,
            'dispel_type'     => 'spelldispeltype.none',
            'icon_name'       => 'inv_misc_questionmark',
            'name'            => 'spells.test',
            'schools_mask'    => 1,
            'description'     => $description,
        ]);
    }
}
