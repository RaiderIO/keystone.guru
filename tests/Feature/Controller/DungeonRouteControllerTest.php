<?php

namespace Tests\Feature\Controller;

use App\Models\Dungeon;
use App\Models\DungeonRoute\DungeonRoute;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('Controller')]
final class DungeonRouteControllerTest extends PublicTestCase
{
    #[Test]
    public function saveNewTemporary_givenEmptyDungeonDifficulty_createsRoute(): void
    {
        // Arrange
        // The difficulty select is empty for non-speedrun dungeons - Tom Select then submits an empty value for it
        $dungeon = $this->getActiveDungeon();

        $dungeonRoute = null;

        try {
            // Act
            $response = $this->post(route('dungeonroute.temporary.savenew'), [
                'dungeon_id'         => $dungeon->id,
                'dungeon_difficulty' => '',
            ]);

            // Assert
            $response->assertSessionHasNoErrors();
            $response->assertRedirect();

            $dungeonRoute = DungeonRoute::query()
                ->where('dungeon_id', $dungeon->id)
                ->orderByDesc('id')
                ->first();
            $this->assertNotNull($dungeonRoute);
            $this->assertNull($dungeonRoute->getRawOriginal('dungeon_difficulty'));
        } finally {
            $dungeonRoute?->delete();
        }
    }

    #[Test]
    public function saveNewTemporary_givenInvalidDungeonDifficulty_returnsValidationError(): void
    {
        // Arrange
        $dungeon = $this->getActiveDungeon();

        // Act
        $response = $this->post(route('dungeonroute.temporary.savenew'), [
            'dungeon_id'         => $dungeon->id,
            'dungeon_difficulty' => 99,
        ]);

        // Assert
        $response->assertSessionHasErrors('dungeon_difficulty');
    }

    private function getActiveDungeon(): Dungeon
    {
        return Dungeon::query()
            ->join('expansions', 'dungeons.expansion_id', '=', 'expansions.id')
            ->where('expansions.active', true)
            ->where('dungeons.active', true)
            ->where('dungeons.speedrun_enabled', false)
            ->select('dungeons.*')
            ->firstOrFail();
    }
}
