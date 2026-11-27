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
    public function GameServerRegion_givenSeededRows_haveKeyAndNoShort(): void
    {
        // Arrange
        $expectedKeysById = array_flip(GameServerRegion::ALL);

        // Act
        $rows = GameServerRegion::query()->orderBy('id')->get(['id', 'key', 'short']);

        // Assert
        $this->assertCount(count($expectedKeysById), $rows);
        foreach ($rows as $row) {
            $this->assertSame($expectedKeysById[$row->id], $row->key, sprintf('%s %d has the wrong key', GameServerRegion::class, $row->id));
            $this->assertNull($row->getRawOriginal('short'), sprintf('%s %d still has a short', GameServerRegion::class, $row->id));
        }
    }

    #[Test]
    public function getUserOrDefaultRegion_givenGuestAndAnotherRegionHoldingTheDefaultShort_returnsRegionWithDefaultKey(): void
    {
        // Arrange
        $id      = GameServerRegion::ALL[GameServerRegion::DEFAULT_REGION];
        $decoyId = GameServerRegion::ALL[GameServerRegion::EUROPE];
        GameServerRegion::query()->whereKey($decoyId)->update(['short' => GameServerRegion::DEFAULT_REGION]);

        try {
            // Act
            $region = GameServerRegion::getUserOrDefaultRegion();

            // Assert
            $this->assertInstanceOf(GameServerRegion::class, $region);
            $this->assertSame($id, $region->id);
            $this->assertSame(GameServerRegion::DEFAULT_REGION, $region->key);
        } finally {
            GameServerRegion::query()->whereKey($decoyId)->update(['short' => null]);
        }
    }

    #[Test]
    public function getRegionEpochByDate_givenEuropeKey_returnsEuEpochChangeDate(): void
    {
        // Arrange
        $europe = GameServerRegion::query()->findOrFail(GameServerRegion::ALL[GameServerRegion::EUROPE]);

        // Act
        $epoch = $europe->getRegionEpochByDate(Carbon::parse(GameServerRegion::EU_EPOCH_CHANGE_STARTED_AT_DATE)->addWeek());

        // Assert
        $this->assertTrue(Carbon::parse(GameServerRegion::EU_EPOCH_CHANGE_DATE)->equalTo($epoch));
    }
}
