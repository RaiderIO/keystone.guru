<?php

namespace Tests\Unit\App\Models;

use App\Models\Dungeon;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('Models')]
final class DungeonImageUrlTest extends PublicTestCase
{
    #[Test]
    public function getImageUrl_givenASeededDungeon_returnsWebpUrl(): void
    {
        // Arrange
        $dungeon = Dungeon::query()->with('expansion')->firstOrFail();

        // Act
        $imageUrl = $dungeon->getImageUrl();

        // Assert
        $this->assertStringEndsWith(
            sprintf('/images/dungeons/%s/%s.webp', $dungeon->expansion->shortname, $dungeon->key),
            $imageUrl,
        );
    }

    #[Test]
    public function getLinkPreviewImageUrl_givenASeededDungeon_returnsJpgUrl(): void
    {
        // Arrange
        $dungeon = Dungeon::query()->with('expansion')->firstOrFail();

        // Act
        $imageUrl = $dungeon->getLinkPreviewImageUrl();

        // Assert
        $this->assertStringEndsWith(
            sprintf('/images/dungeons/%s/%s.jpg', $dungeon->expansion->shortname, $dungeon->key),
            $imageUrl,
        );
    }
}
