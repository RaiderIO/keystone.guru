<?php

namespace Tests\Feature\App\Models\GameVersion;

use App\Models\GameVersion\GameVersion;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('GameVersion')]
final class GameVersionRetiredTest extends PublicTestCase
{
    #[Test]
    #[DataProvider('retiredGameVersionKeyProvider')]
    public function retiredIntoGameVersion_givenSeededRetiredGameVersion_returnsRetail(string $retiredGameVersionKey): void
    {
        // Arrange
        $gameVersion = GameVersion::query()->where('key', $retiredGameVersionKey)->firstOrFail();

        // Act
        $retiredIntoGameVersion = $gameVersion->retiredIntoGameVersion;

        // Assert
        $this->assertTrue($gameVersion->isRetired());
        $this->assertFalse((bool)$gameVersion->active);
        $this->assertSame(GameVersion::GAME_VERSION_RETAIL, $retiredIntoGameVersion?->key);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function retiredGameVersionKeyProvider(): array
    {
        return [
            'wrath'        => [GameVersion::GAME_VERSION_WRATH],
            'cata'         => [GameVersion::GAME_VERSION_CATA],
            'legion remix' => [GameVersion::GAME_VERSION_LEGION_REMIX],
        ];
    }

    #[Test]
    #[DataProvider('notRetiredGameVersionKeyProvider')]
    public function isRetired_givenSeededGameVersionThatIsNotRetired_returnsFalse(string $gameVersionKey): void
    {
        // Arrange
        $gameVersion = GameVersion::query()->where('key', $gameVersionKey)->firstOrFail();

        // Act
        $isRetired = $gameVersion->isRetired();

        // Assert
        $this->assertFalse($isRetired);
        $this->assertNull($gameVersion->retiredIntoGameVersion);
    }

    /**
     * Beta is inactive without being retired, so inactivity alone does not count.
     *
     * @return array<string, array{string}>
     */
    public static function notRetiredGameVersionKeyProvider(): array
    {
        return [
            'retail'      => [GameVersion::GAME_VERSION_RETAIL],
            'classic era' => [GameVersion::GAME_VERSION_CLASSIC_ERA],
            'mop'         => [GameVersion::GAME_VERSION_MOP],
            'beta'        => [GameVersion::GAME_VERSION_BETA],
            'forever'     => [GameVersion::GAME_VERSION_FOREVER],
        ];
    }
}
