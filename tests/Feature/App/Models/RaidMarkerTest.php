<?php

namespace Tests\Feature\App\Models;

use App\Models\RaidMarker;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('RaidMarker')]
final class RaidMarkerTest extends PublicTestCase
{
    #[Test]
    public function RaidMarker_givenSeededRows_haveTranslationKeyName(): void
    {
        // Arrange
        $expectedKeysById = array_flip(RaidMarker::ALL);

        // Act
        $rows = RaidMarker::query()->orderBy('id')->get(['id', 'key', 'name']);

        // Assert
        $this->assertCount(count($expectedKeysById), $rows);
        foreach ($rows as $row) {
            $this->assertSame($expectedKeysById[$row->id], $row->key, sprintf('%s %d has the wrong key', RaidMarker::class, $row->id));
            $this->assertSame(sprintf('raidmarkers.%s', $row->key), $row->name, sprintf('%s %d has the wrong translation key as its name', RaidMarker::class, $row->id));
            $this->assertNotSame($row->name, __($row->name), sprintf('%s %d has a name that does not translate', RaidMarker::class, $row->id));
        }
    }

    #[Test]
    public function imageUrl_givenSeededRaidMarker_returnsAssetUrlForItsKey(): void
    {
        // Arrange
        $raidMarker = RaidMarker::query()->findOrFail(RaidMarker::ALL[RaidMarker::RAID_MARKER_SKULL]);

        // Act
        $imageUrl = $raidMarker->image_url;

        // Assert
        $this->assertSame(ksgAssetImage('mapicon/raid_marker_skull.png'), $imageUrl);
    }
}
