<?php

namespace Tests\Feature\App\Service\MDT;

use App\Logic\MDT\Exception\ImportWarning;
use App\Models\MapIcon;
use App\Service\MDT\MDTExportStringServiceInterface;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;

#[Group('MDT')]
#[Group('MDTExportStringService')]
final class MDTExportStringServiceNonAsciiTest extends MDTExportStringServiceTestBase
{
    private const string NON_ASCII_TITLE = 'Маршрут «Тест» — 路线 ✓';

    private const string NON_ASCII_COMMENT = 'Пропустить эту группу — 跳过 ☠';

    #[Test]
    public function getEncodedString_givenNonAsciiTitleAndMapIconComment_returnsStringCarryingThemUnchanged(): void
    {
        $dungeonRoute = null;

        try {
            // Arrange
            $dungeonRoute = $this->getMDTCompatibleNonFacadeDungeonRoute();
            $dungeonRoute->update(['title' => self::NON_ASCII_TITLE]);

            /** @var MapIcon $mapIcon */
            $mapIcon = MapIcon::factory()->create(['comment' => self::NON_ASCII_COMMENT]);
            $dungeonRoute->mapIcons()->save($mapIcon);

            /** @var Collection<int, ImportWarning> $warnings */
            $warnings = new Collection();

            // Act
            $encodedString = app()->make(MDTExportStringServiceInterface::class)
                ->setDungeonRoute($dungeonRoute)
                ->getEncodedString($warnings, false);

            // Assert
            $decoded = $this->decode($encodedString);
            $this->assertSame(self::NON_ASCII_TITLE, $decoded['text']);
            $this->assertCount(1, $decoded['objects']);
            $this->assertSame(self::NON_ASCII_COMMENT, $decoded['objects'][0]['d'][4]);
            $this->assertTrue($warnings->isEmpty());
        } finally {
            $dungeonRoute?->delete();
        }
    }
}
