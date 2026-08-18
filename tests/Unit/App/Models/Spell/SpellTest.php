<?php

namespace Tests\Unit\App\Models\Spell;

use App\Models\Spell\Spell;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

/**
 * Guards against #4095 - a raw, unprefixed or otherwise unrecognised `dispel_type` value must never
 * be surfaced verbatim in the tooltip, and a genuinely informative one must render translated.
 */
#[Group('Models')]
final class SpellTest extends PublicTestCase
{
    private function makeSpell(string $dispelType): Spell
    {
        return new Spell([
            'description_format' => 'Some description',
            'dispel_type'        => $dispelType,
            'schools_mask'       => 0,
            'cast_time'          => 0,
            'duration'           => 0,
        ]);
    }

    #[Test]
    public function getTooltipDataAttribute_givenPrefixedInformativeDispelType_rendersTranslatedDispelType(): void
    {
        // Arrange
        $spell = $this->makeSpell('spelldispeltype.magic');

        // Act
        $tooltipData = $spell->tooltip_data;

        // Assert
        $this->assertSame('Magic', $tooltipData['dispelType']);
    }

    #[Test]
    #[DataProvider('uninformativeOrDriftedDispelTypeProvider')]
    public function getTooltipDataAttribute_givenUninformativeOrDriftedDispelType_omitsDispelTypeRow(string $dispelType): void
    {
        // Arrange
        $spell = $this->makeSpell($dispelType);

        // Act
        $tooltipData = $spell->tooltip_data;

        // Assert
        $this->assertArrayNotHasKey('dispelType', $tooltipData);
    }

    /**
     * @return array<string, array<int, string>>
     */
    public static function uninformativeOrDriftedDispelTypeProvider(): array
    {
        return [
            'prefixed none'      => ['spelldispeltype.none'],
            'prefixed n/a (Sap)' => ['spelldispeltype.n_a'],
            'prefixed unknown'   => ['spelldispeltype.unknown'],
            'empty string'       => [''],
            // Unprefixed legacy/drifted values (#4095) - must be suppressed, never printed raw.
            'unprefixed magic'       => ['magic'],
            'unprefixed n_a'         => ['n_a'],
            'unprefixed unknown'     => ['unknown'],
            'not a real dispel type' => ['spelldispeltype.does_not_exist'],
        ];
    }
}
