<?php

namespace Tests\Unit\Database\Seeders;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

#[Group('DatabaseSeeder')]
final class EnemySeederDataTest extends TestCase
{
    /**
     * Far above any id an import hands out, and far below the 9999999xx ids tests insert explicitly - an id past this
     * came from an auto increment a test row pushed up, and every database seeded from it continues from there.
     */
    private const int MAX_PLAUSIBLE_ENEMY_ID = 10_000_000;

    #[Test]
    public function enemiesJson_givenEverySeededEnemy_hasAnIdBelowTheLeakedTestIdRange(): void
    {
        // Arrange
        $filePaths = glob(database_path('seeders/dungeondata/*/*/*/enemies.json'));
        $this->assertNotEmpty($filePaths, 'No enemy seeder files found.');

        // Act
        $checkedCount = 0;
        $tooHighIds   = [];
        foreach ($filePaths as $filePath) {
            $contents = file_get_contents($filePath);
            $this->assertIsString($contents, sprintf('Unable to read %s.', $filePath));

            foreach (json_decode($contents, true, flags: JSON_THROW_ON_ERROR) as $enemy) {
                $checkedCount++;
                if ($enemy['id'] >= self::MAX_PLAUSIBLE_ENEMY_ID) {
                    $tooHighIds[] = sprintf('%d (%s)', $enemy['id'], $filePath);
                }
            }
        }

        // Assert
        $this->assertGreaterThan(0, $checkedCount);
        $this->assertSame([], array_slice($tooHighIds, 0, 10), sprintf(
            '%d seeded enemies have an id of %d or more - reset the enemies AUTO_INCREMENT and renumber them.',
            count($tooHighIds),
            self::MAX_PLAUSIBLE_ENEMY_ID,
        ));
    }
}
