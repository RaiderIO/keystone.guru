<?php

namespace Tests\Feature\App\Service\MDT;

use App\Models\Enemy;
use App\Models\KillZone\KillZone;
use App\Models\KillZone\KillZoneEnemy;
use App\Models\MapIcon;
use App\Service\MDT\MDTExportStringServiceInterface;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;

#[Group('UsesLua')]
#[Group('MDTExportStringService')]
class MDTExportStringServiceExtractObjectsTest extends MDTExportStringServiceTestBase
{
    #[Test]
    #[Group('MDTExportStringServiceExtractObjects')]
    public function extractObjects_givenMapIconWithLinkInComment_shouldExportToMdtWithUrlIntact(): void
    {
        $dungeonRoute = null;

        try {
            // Arrange
            $mdtExportStringService = app()->make(MDTExportStringServiceInterface::class);
            $url                    = 'https://raider.io/some_article';

            $dungeonRoute = $this->getMDTCompatibleNonFacadeDungeonRoute();

            /** @var MapIcon $mapIcon */
            $mapIcon = MapIcon::factory()->create();
            $dungeonRoute->mapIcons()->save($mapIcon);

            // The comment mutator now strips HTML on write; update through the query builder to
            // stand in for a row saved before that stripping existed.
            MapIcon::query()->whereKey($mapIcon->id)->update([
                'comment' => sprintf('some string <a href="%s">link text</a>', $url),
            ]);
            $mapIcon->refresh();

            $warnings = collect();

            // Act
            $encodedString = $mdtExportStringService->setDungeonRoute($dungeonRoute)->getEncodedString($warnings);

            // Assert
            $decodedString = $this->decode($encodedString);

            Assert::assertIsArray($decodedString);
            Assert::assertEmpty($warnings);
            Assert::assertEquals(sprintf('some string (%s)', $url), $decodedString['objects'][0]['d'][4]);
        } finally {
            $dungeonRoute?->delete();
        }
    }

    #[Test]
    #[Group('MDTExportStringServiceExtractObjects')]
    public function extractObjects_givenKillZoneWithLinkInDescription_shouldExportToMdtWithUrlIntact(): void
    {
        $dungeonRoute = null;

        try {
            // Arrange
            $mdtExportStringService = app()->make(MDTExportStringServiceInterface::class);
            $url                    = 'https://raider.io/some_article';

            $dungeonRoute = $this->getMDTCompatibleDungeonRouteWithSafeEnemies();

            /** @var Collection<int, Enemy> $randomEnemies */
            $randomEnemies = $this->getSafeMdtEnemies($dungeonRoute);

            foreach ($randomEnemies as $randomEnemy) {
                /** @var KillZone $killZone */
                $killZone = KillZone::factory()->create([
                    'description' => sprintf('some string <a href="%s">link text</a>', $url),
                ]);

                /** @var KillZoneEnemy $killZoneEnemy */
                $killZoneEnemy = KillZoneEnemy::factory()->create([
                    'kill_zone_id' => $killZone->id,
                    'npc_id'       => $randomEnemy->npc_id,
                    'mdt_id'       => $randomEnemy->mdt_id,
                    'enemy_id'     => $randomEnemy->id,
                ]);
                $killZone->killZoneEnemies()->save($killZoneEnemy);
                $dungeonRoute->killZones()->save($killZone);
            }

            $warnings = collect();

            // Act
            $encodedString = $mdtExportStringService->setDungeonRoute($dungeonRoute)->getEncodedString($warnings);

            // Assert
            $decodedString = $this->decode($encodedString);

            Assert::assertIsArray($decodedString);
            Assert::assertEmpty($warnings);
            Assert::assertEquals(sprintf('some string (%s)', $url), $decodedString['objects'][0]['d'][4]);
        } finally {
            $dungeonRoute?->delete();
        }
    }
}
