<?php

namespace Tests\Feature\App\Service\MDT;

use App\Console\Commands\Traits\ConvertsMDTStrings;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Floor\Floor;
use App\Models\MapIcon;
use App\Service\MDT\MDTExportStringServiceInterface;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

class MDTExportStringServiceTest extends PublicTestCase
{
    use ConvertsMDTStrings;

    #[Test]
    #[Group('MDTExportStringService')]
    public function extractObjects_givenMapIconWithLinkInComment_shouldExportToMDTWithUrlIntact(): void
    {
        // Arrange
        $mdtExportStringService = app()->make(MDTExportStringServiceInterface::class);
        $url                    = 'https://raider.io/some_article';

        /** @var DungeonRoute $dungeonRoute */
        $dungeonRoute = DungeonRoute::factory()->create();
        /** @var Floor $randomFloor */
        $randomFloor = $dungeonRoute->dungeon->floors->random();
        /** @var MapIcon $mapIcon */
        $mapIcon = MapIcon::factory()->create([
            'comment' => sprintf('some string <a href="%s">link text</a>', $url),
        ]);
        $mapIcon->dungeonRoute()->associate($dungeonRoute);
        $mapIcon->floor()->associate($randomFloor);
        $warnings = collect();

        // Act
        $encodedString = $mdtExportStringService->setDungeonRoute($dungeonRoute)->getEncodedString($warnings);


        // Assert
        $decodedString = json_decode($this->decode($encodedString), true);

        Assert::assertIsArray($decodedString);
        dump($decodedString);
        Assert::assertEmpty($warnings);
        Assert::assertEquals(sprintf('some string (%s)', $url), 'todo');
    }
}
