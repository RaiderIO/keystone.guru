<?php

namespace Tests\Feature\Controller\Admin;

use App\Models\DungeonDifficulty;
use App\Models\Floor\Floor;
use App\Models\User;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('Controller')]
#[Group('Admin')]
final class DungeonSpeedrunRequiredNpcsControllerTest extends PublicTestCase
{
    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->be(User::findOrFail(1));
    }

    #[Test]
    public function create_givenValidDifficulty_returnsOk(): void
    {
        // Arrange
        $floor = Floor::whereNotNull('dungeon_id')->firstOrFail();

        // Act
        $response = $this->get(route('admin.dungeonspeedrunrequirednpc.new', [
            'dungeon'    => $floor->dungeon,
            'floor'      => $floor,
            'difficulty' => DungeonDifficulty::TEN_MAN->value,
        ]));

        // Assert
        $response->assertOk();
    }

    #[Test]
    public function create_givenDifficultyOutsideDeclaredValues_returnsNotFound(): void
    {
        // Arrange
        $floor                = Floor::whereNotNull('dungeon_id')->firstOrFail();
        $undeclaredDifficulty = max(DungeonDifficulty::values()) + 1;

        // Act
        $response = $this->get(route('admin.dungeonspeedrunrequirednpc.new', [
            'dungeon'    => $floor->dungeon,
            'floor'      => $floor,
            'difficulty' => $undeclaredDifficulty,
        ]));

        // Assert
        $response->assertNotFound();
    }
}
