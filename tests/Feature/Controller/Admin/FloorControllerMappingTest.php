<?php

namespace Tests\Feature\Controller\Admin;

use App\Models\Dungeon;
use App\Models\User;
use App\Service\Dungeon\DungeonServiceInterface;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('Controller')]
#[Group('Admin')]
#[Group('Mapping')]
final class FloorControllerMappingTest extends PublicTestCase
{
    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->be(User::findOrFail(1));
    }

    #[Test]
    public function mapping_asAdmin_linksOtherDungeonsToTheirNewestMappingVersion(): void
    {
        // Arrange
        // The dungeon-context strip only shows dungeons for the user's current game version - pick
        // from that same set, or the expected link would never appear in the header regardless of fix.
        $contextDungeons = app(DungeonServiceInterface::class)->getDungeonsForGameVersion()
            ->filter(static fn(Dungeon $dungeon): bool => $dungeon->floors()->exists() && $dungeon->getCurrentMappingVersion() !== null)
            ->values();

        if ($contextDungeons->count() < 2) {
            $this->fail('Need at least 2 mapped dungeons in the current game version context to run this test.');
        }

        $dungeon = $contextDungeons->first();
        $floor   = $dungeon->floors()->firstOrFail();

        $otherDungeon                     = $contextDungeons->get(1);
        $otherDungeonNewestFloor          = $otherDungeon->floors()->firstOrFail();
        $otherDungeonNewestMappingVersion = $otherDungeon->getCurrentMappingVersion();

        // Act
        $response = $this->get(route('admin.floor.edit.mapping', [
            'dungeon'         => $dungeon,
            'floor'           => $floor,
            'mapping_version' => $dungeon->getCurrentMappingVersion(),
        ]));

        // Assert
        $response->assertOk();
        $response->assertSee(route('admin.floor.edit.mapping', [
            'dungeon'         => $otherDungeon,
            'floor'           => $otherDungeonNewestFloor,
            'mapping_version' => $otherDungeonNewestMappingVersion,
        ]), false);
    }
}
