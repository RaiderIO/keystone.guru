<?php

namespace Tests\Unit\App\Models\Spell;

use App\Models\Spell\KnownSpell;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('Models')]
final class KnownSpellTest extends PublicTestCase
{
    #[Test]
    public function bloodlustySpells_givenTheList_containsEveryBloodlustEffectInOrder(): void
    {
        // Arrange
        $expected = [2825, 32182, 80353, 390386, 90355, 264667, 381301, 444257, 466904];

        // Act
        $bloodlustySpells = KnownSpell::BLOODLUSTY_SPELLS;

        // Assert
        $this->assertSame($expected, $bloodlustySpells);
    }

    #[Test]
    public function charmSpells_givenTheList_containsControlUndeadAndSubjugateDemon(): void
    {
        // Arrange
        $expected = [111673, 1098];

        // Act
        $charmSpells = KnownSpell::CHARM_SPELLS;

        // Assert
        $this->assertSame($expected, $charmSpells);
    }

    #[Test]
    public function immunitySpells_givenTheList_containsEveryImmunityInOrder(): void
    {
        // Arrange
        $expected = [642, 45438, 186265, 1022, 204018, 48707];

        // Act
        $immunitySpells = KnownSpell::IMMUNITY_SPELLS;

        // Assert
        $this->assertSame($expected, $immunitySpells);
    }

    #[Test]
    public function tryFrom_givenTheSpellIdOfACase_returnsThatCase(): void
    {
        // Arrange
        $spellId = 2825;

        // Act
        $knownSpell = KnownSpell::tryFrom($spellId);

        // Assert
        $this->assertSame(KnownSpell::Bloodlust, $knownSpell);
    }
}
