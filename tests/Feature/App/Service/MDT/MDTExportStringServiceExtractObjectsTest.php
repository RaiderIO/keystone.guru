<?php

namespace Tests\Feature\App\Service\MDT;

use App\Models\Enemy;
use App\Models\KillZone\KillZone;
use App\Models\KillZone\KillZoneEnemy;
use App\Models\MapIcon;
use App\Service\MDT\MDTExportStringServiceInterface;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;

#[Group('UsesLua')]
#[Group('MDTExportStringService')]
class MDTExportStringServiceExtractObjectsTest extends MDTExportStringServiceTestBase
{
    #[Test]
    #[Group('MDTExportStringServiceExtractObjects')]
    public function extractObjects_givenMapIconWithLinkInComment_shouldExportToMDTWithUrlIntact(): void
    {
        $dungeonRoute = null;

        try {
            // Arrange
            $mdtExportStringService = app()->make(MDTExportStringServiceInterface::class);
            $url                    = 'https://raider.io/some_article';

            $dungeonRoute = $this->getMDTCompatibleDungeonRoute();

            /** @var MapIcon $mapIcon */
            $mapIcon = MapIcon::factory()->create([
                'comment' => sprintf('some string <a href="%s">link text</a>', $url),
            ]);
            $dungeonRoute->mapIcons()->save($mapIcon);

            $warnings = collect();

            // Act
            $encodedString = $mdtExportStringService->setDungeonRoute($dungeonRoute)->getEncodedString($warnings);

            // Assert
            $decodedString = json_decode($this->decode($encodedString), true);

            Assert::assertIsArray($decodedString);
            Assert::assertEmpty($warnings);
            Assert::assertEquals(sprintf('some string (%s)', $url), $decodedString['objects'][0]['d'][4]);
        } finally {
            $dungeonRoute?->delete();
        }
    }

    #[Test]
    #[Group('MDTExportStringServiceExtractObjects')]
    public function extractObjects_givenKillZoneWithLinkInDescription_shouldExportToMDTWithUrlIntact(): void
    {
        $dungeonRoute = null;

        try {
            // Arrange
            $mdtExportStringService = app()->make(MDTExportStringServiceInterface::class);
            $url                    = 'https://raider.io/some_article';

            $dungeonRoute = $this->getMDTCompatibleDungeonRoute();

            /** @var Enemy $randomEnemy */
            $randomEnemy = Enemy::where('mapping_version_id', $dungeonRoute->mapping_version_id)
                ->whereNull('teeming')
                ->whereNull('seasonal_type')
                ->inRandomOrder()
                ->first();
            /** @var KillZone $killZone */
            $killZone = KillZone::factory()->create([
                'description' => sprintf('some string <a href="%s">link text</a>', $url),
            ]);

            $killZoneEnemies = KillZoneEnemy::factory()->forEnemy($randomEnemy)->count(1)->create([
                'kill_zone_id' => $killZone->id,
            ]);
            $killZone->killZoneEnemies()->saveMany($killZoneEnemies);
            $dungeonRoute->killZones()->save($killZone);

            $warnings = collect();

            // Act
            $encodedString = $mdtExportStringService->setDungeonRoute($dungeonRoute)->getEncodedString($warnings);

            // Assert
            $decodedString = json_decode($this->decode($encodedString), true);

            Assert::assertIsArray($decodedString);
            Assert::assertEmpty($warnings);
            Assert::assertEquals(sprintf('some string (%s)', $url), $decodedString['objects'][0]['d'][4]);
        } finally {
            $dungeonRoute?->delete();
        }
    }
}
