<?php

namespace Tests\Feature\App\Models;

use App\Models\GameServerRegion;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('GameServerRegion')]
final class GameServerRegionTest extends PublicTestCase
{
    #[Test]
    public function GameServerRegion_givenSeededRows_haveKeyMatchingShort(): void
    {
        // Arrange
        $expectedKeysById = array_flip(GameServerRegion::ALL);

        // Act
        $rows = GameServerRegion::query()->orderBy('id')->get(['id', 'key', 'short']);

        // Assert
        $this->assertCount(count($expectedKeysById), $rows);
        foreach ($rows as $row) {
            $this->assertSame($expectedKeysById[$row->id], $row->key, sprintf('%s %d has the wrong key', GameServerRegion::class, $row->id));
            $this->assertSame($row->short, $row->key, sprintf('%s %d has a key that differs from its short', GameServerRegion::class, $row->id));
        }
    }

    #[Test]
    public function getUserOrDefaultRegion_givenGuestAndLegacyShortDiffers_returnsRegionWithDefaultKey(): void
    {
        // Arrange
        $id = GameServerRegion::ALL[GameServerRegion::DEFAULT_REGION];
        GameServerRegion::query()->whereKey($id)->update(['short' => sprintf('legacy_%s', GameServerRegion::DEFAULT_REGION)]);

        try {
            // Act
            $region = GameServerRegion::getUserOrDefaultRegion();

            // Assert
            $this->assertInstanceOf(GameServerRegion::class, $region);
            $this->assertSame($id, $region->id);
            $this->assertSame(GameServerRegion::DEFAULT_REGION, $region->key);
        } finally {
            GameServerRegion::query()->whereKey($id)->update(['short' => GameServerRegion::DEFAULT_REGION]);
        }
    }

    #[Test]
    public function getRegionEpochByDate_givenEuropeKeyWithLegacyShort_returnsEuEpochChangeDate(): void
    {
        // Arrange
        $europe        = GameServerRegion::query()->findOrFail(GameServerRegion::ALL[GameServerRegion::EUROPE]);
        $europe->short = sprintf('legacy_%s', GameServerRegion::EUROPE);

        // Act
        $epoch = $europe->getRegionEpochByDate(Carbon::parse(GameServerRegion::EU_EPOCH_CHANGE_STARTED_AT_DATE)->addWeek());

        // Assert
        $this->assertTrue(Carbon::parse(GameServerRegion::EU_EPOCH_CHANGE_DATE)->equalTo($epoch));
    }
}
