<?php

namespace Tests\Feature\App\Http\Resources\AffixGroup;

use App\Http\Resources\AffixGroup\AffixGroupResource;
use App\Models\AffixGroup\AffixGroup;
use App\Models\Expansion;
use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('AffixGroupResource')]
final class AffixGroupResourceTest extends PublicTestCase
{
    #[Test]
    public function toArray_givenExpansionWithLegacyShortname_emitsExpansionKey(): void
    {
        // Arrange
        $expansionId = Expansion::ALL[Expansion::EXPANSION_TWW];
        Expansion::query()->whereKey($expansionId)->update(['shortname' => sprintf('legacy_%s', Expansion::EXPANSION_TWW)]);

        try {
            $affixGroup = AffixGroup::query()->with(['expansion', 'affixes'])->where('expansion_id', $expansionId)->firstOrFail();

            // Act
            $result = new AffixGroupResource($affixGroup)->toArray(new Request());

            // Assert
            $this->assertSame(Expansion::EXPANSION_TWW, $result['expansion']);
        } finally {
            Expansion::query()->whereKey($expansionId)->update(['shortname' => Expansion::EXPANSION_TWW]);
        }
    }
}
