<?php

namespace Tests\Feature\App\Service\View;

use App\Models\GameVersion\GameVersion;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fixtures\ServiceFixtures;
use Tests\TestCases\PublicTestCase;

#[Group('ViewService')]
#[Group('GameVersion')]
final class ViewServiceGetAllGameVersionsTest extends PublicTestCase
{
    /**
     * The seeded ids follow the order the versions were added to the site, not the order they were released in.
     */
    #[Test]
    public function getAllGameVersions_givenADisplayOrderUnlikeTheIds_returnsThemByDisplayOrder(): void
    {
        // Arrange
        Cache::store('tmp_file')->flush();

        $mop                  = GameVersion::query()->where('key', GameVersion::GAME_VERSION_MOP)->firstOrFail();
        $originalDisplayOrder = $mop->display_order;

        try {
            GameVersion::query()->whereKey($mop->id)->update(['display_order' => 0]);

            // Act
            $gameVersions = ServiceFixtures::getViewServiceMock($this)->getAllGameVersions();

            // Assert
            $this->assertSame(GameVersion::GAME_VERSION_MOP, $gameVersions->first()->key);
            $this->assertSame($gameVersions->sortBy('display_order')->pluck('key')->all(), $gameVersions->pluck('key')->all());
        } finally {
            GameVersion::query()->whereKey($mop->id)->update(['display_order' => $originalDisplayOrder]);
            Cache::store('tmp_file')->flush();
        }
    }
}
