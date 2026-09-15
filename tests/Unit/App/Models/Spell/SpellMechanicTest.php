<?php

namespace Tests\Unit\App\Models\Spell;

use App\Models\Spell\SpellMechanic;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('Models')]
final class SpellMechanicTest extends PublicTestCase
{
    #[Test]
    public function blizzardId_givenEveryCase_returnsBlizzardsIdForThatMechanic(): void
    {
        // Arrange
        $expected = [
            'asleep'        => 10,
            'banished'      => 18,
            'bleeding'      => 15,
            'charmed'       => 1,
            'gripped'       => 6,
            'dazed'         => 27,
            'disarmed'      => 3,
            'discovery'     => 28,
            'disoriented'   => 2,
            'distracted'    => 4,
            'enraged'       => 31,
            'snared'        => 11,
            'fleeing'       => 5,
            'frozen'        => 13,
            'healing'       => 16,
            'horrified'     => 24,
            'incapacitated' => 14,
            'interrupted'   => 26,
            'invulnerable'  => 29,
            'mounted'       => 21,
            'slowed'        => 8,
            'polymorphed'   => 17,
            'rooted'        => 7,
            'sapped'        => 30,
            'infected'      => 22,
            'shackled'      => 20,
            'shielded'      => 19,
            'silenced'      => 9,
            'stunned'       => 12,
            'turned'        => 23,
            'wounded'       => 32,
        ];

        // Act
        $blizzardIds = [];
        foreach (SpellMechanic::cases() as $mechanic) {
            $blizzardIds[$mechanic->value] = $mechanic->blizzardId();
        }

        // Assert
        $this->assertSame($expected, $blizzardIds);
    }

    #[Test]
    public function blizzardId_givenAllCases_isUniquePerMechanic(): void
    {
        // Arrange
        $blizzardIds = array_map(static fn(SpellMechanic $mechanic): int => $mechanic->blizzardId(), SpellMechanic::cases());

        // Act
        $uniqueBlizzardIds = array_unique($blizzardIds);

        // Assert
        $this->assertCount(count($blizzardIds), $uniqueBlizzardIds);
    }
}
