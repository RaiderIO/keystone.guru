<?php

namespace Tests\Unit\Database\Seeders;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

#[Group('DatabaseSeeder')]
final class MapIconSeederDataTest extends TestCase
{
    #[Test]
    public function mapIconsJson_givenTranslationKeyComment_resolvesToEnglishTranslation(): void
    {
        // Arrange
        $filePaths = glob(database_path('seeders/dungeondata/*/*/*/map_icons.json'));
        $this->assertNotEmpty($filePaths, 'No map icon seeder files found.');

        // Act
        $checkedKeys = [];
        $missingKeys = [];
        foreach ($filePaths as $filePath) {
            $contents = file_get_contents($filePath);
            $this->assertIsString($contents, sprintf('Unable to read %s.', $filePath));

            foreach (json_decode($contents, true, flags: JSON_THROW_ON_ERROR) as $mapIcon) {
                $comment = $mapIcon['comment'] ?? null;
                if (!is_string($comment) || !str_starts_with($comment, 'mapping.map_icons.') || isset($checkedKeys[$comment])) {
                    continue;
                }

                $checkedKeys[$comment] = true;
                if (__($comment, [], 'en_US') === $comment) {
                    $missingKeys[] = sprintf('%s (%s)', $comment, $filePath);
                }
            }
        }

        // Assert
        $this->assertNotEmpty($checkedKeys, 'No map icon carries a translation key comment.');
        $this->assertSame([], $missingKeys, 'Every map icon translation key comment must exist in lang/en_US/mapping.php.');
    }
}
