<?php

namespace Tests\Unit\App\Models;

use App\Models\DungeonRoute\DungeonRoute;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('Models')]
#[Group('DungeonRoute')]
final class DungeonRouteSanitizedDescriptionTest extends PublicTestCase
{
    private const string UNSANITISED_DESCRIPTION = 'Pull left<img src=x onerror="alert(1)"><b onclick="alert(1)">now</b><script>x()</script>';
    private const string SANITISED_DESCRIPTION   = 'Pull left<b>now</b>x()';

    #[Test]
    #[DataProvider('getSanitizedDescription_dataProvider')]
    public function getSanitizedDescription_givenStoredDescription_returnsSafeHtml(string $stored, string $expected): void
    {
        // Arrange
        $dungeonRoute = new DungeonRoute();
        $dungeonRoute->setRawAttributes(['description' => $stored]);

        // Act
        $result = $dungeonRoute->getSanitizedDescription();

        // Assert
        $this->assertSame($expected, $result);
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function getSanitizedDescription_dataProvider(): array
    {
        return [
            'unsanitised markup'        => [self::UNSANITISED_DESCRIPTION, self::SANITISED_DESCRIPTION],
            'already sanitised markup'  => ['Tom &amp; Jerry <b>bold</b><br>"hi"', 'Tom &amp; Jerry <b>bold</b><br>"hi"'],
            'plain text with a newline' => ["Tom & Jerry\n\"hi\"", "Tom & Jerry\n\"hi\""],
            'markup with a newline'     => ["<b>Tom</b>\nJerry", "<b>Tom</b>\nJerry"],
            'empty'                     => ['', ''],
        ];
    }

    #[Test]
    public function attributesToArray_givenUnsanitisedDescription_returnsSanitisedDescription(): void
    {
        // Arrange
        $dungeonRoute = new DungeonRoute();
        $dungeonRoute->setRawAttributes(['title' => 'Route', 'description' => self::UNSANITISED_DESCRIPTION]);

        // Act
        $result = $dungeonRoute->attributesToArray();

        // Assert
        $this->assertSame(self::SANITISED_DESCRIPTION, $result['description']);
        $this->assertSame('Route', $result['title']);
        $this->assertSame(self::UNSANITISED_DESCRIPTION, $dungeonRoute->description, 'The stored attribute itself is left alone');
    }

    #[Test]
    public function attributesToArray_givenHiddenDescription_returnsNoDescription(): void
    {
        // Arrange
        $dungeonRoute = new DungeonRoute();
        $dungeonRoute->setRawAttributes(['title' => 'Route', 'description' => self::UNSANITISED_DESCRIPTION]);
        $dungeonRoute->makeHidden('description');

        // Act
        $result = $dungeonRoute->attributesToArray();

        // Assert
        $this->assertArrayNotHasKey('description', $result);
        $this->assertSame('Route', $result['title']);
    }
}
