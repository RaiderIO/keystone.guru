<?php

namespace Tests\Feature\App\Http\Resources\Dungeon;

use App\Http\Resources\Dungeon\DungeonResource;
use App\Models\Dungeon;
use App\Models\Expansion;
use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('DungeonResource')]
final class DungeonResourceTest extends PublicTestCase
{
    #[Test]
    public function toArray_givenExpansionWithLegacyShortname_emitsExpansionKey(): void
    {
        // Arrange
        $expansionId = Expansion::ALL[Expansion::EXPANSION_TWW];
        Expansion::query()->whereKey($expansionId)->update(['shortname' => sprintf('legacy_%s', Expansion::EXPANSION_TWW)]);

        try {
            $dungeon = Dungeon::query()->with(['expansion', 'floors'])->where('expansion_id', $expansionId)->firstOrFail();

            // Act
            $result = new DungeonResource($dungeon)->toArray(new Request());

            // Assert
            $this->assertSame(Expansion::EXPANSION_TWW, $result['expansion']);
        } finally {
            Expansion::query()->whereKey($expansionId)->update(['shortname' => Expansion::EXPANSION_TWW]);
        }
    }
}
