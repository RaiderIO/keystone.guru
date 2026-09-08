<?php

namespace Tests\Unit\App\Models;

use App\Models\Enemy;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

/**
 * The enemy_display_type cookie is written by the front-end, so the value read back may be empty or
 * left over from an older client. {@see Enemy::sanitizeDisplayType()} is what stops such a value from
 * reaching the map, where an unrecognised type renders as npc_class and is then written back to the
 * cookie as the user's stored preference.
 */
#[Group('Models')]
final class EnemyDisplayTypeTest extends PublicTestCase
{
    #[Test]
    #[DataProvider('malformedDisplayTypeProvider')]
    public function sanitizeDisplayType_givenAMalformedValue_returnsTheDefault(mixed $displayType): void
    {
        // Arrange
        // Act
        $result = Enemy::sanitizeDisplayType($displayType);

        // Assert
        $this->assertSame(Enemy::DISPLAY_TYPE_DEFAULT, $result);
    }

    /** @return array<string, array{0: mixed}> */
    public static function malformedDisplayTypeProvider(): array
    {
        return [
            'null'                    => [null],
            'empty string'            => [''],
            'the string "undefined"'  => ['undefined'],
            'an unknown display type' => ['not_a_display_type'],
            'a non-string'            => [1],
        ];
    }

    #[Test]
    #[DataProvider('knownDisplayTypeProvider')]
    public function sanitizeDisplayType_givenAKnownValue_returnsItUnchanged(string $displayType): void
    {
        // Arrange
        // Act
        $result = Enemy::sanitizeDisplayType($displayType);

        // Assert
        $this->assertSame($displayType, $result);
    }

    /** @return array<int, array{0: string}> */
    public static function knownDisplayTypeProvider(): array
    {
        return array_map(static fn(string $displayType): array => [$displayType], Enemy::DISPLAY_TYPE_ALL);
    }

    #[Test]
    public function displayTypeAll_givenTheJavascriptConstants_matchesThemExactly(): void
    {
        // Arrange
        $constantsJs = file_get_contents(resource_path('assets/js/custom/constants.js'));
        preg_match('/const DISPLAY_TYPE_ALL = \[(.*?)\];/s', $constantsJs, $listMatch);

        // Act
        preg_match_all('/const (DISPLAY_TYPE_[A-Z_]+) = \'([a-z_]+)\';/', $constantsJs, $constantMatches);
        $javascriptValues = array_combine($constantMatches[1], $constantMatches[2]);
        preg_match_all('/DISPLAY_TYPE_[A-Z_]+/', $listMatch[1] ?? '', $listedConstants);
        $javascriptDisplayTypes = array_map(
            static fn(string $constant): ?string => $javascriptValues[$constant] ?? null,
            $listedConstants[0],
        );

        // Assert
        $this->assertSame(
            Enemy::DISPLAY_TYPE_ALL,
            $javascriptDisplayTypes,
            'The display types the front-end accepts must match the ones the back-end sanitizes against, '
            . 'or a type valid on one side is silently rewritten to the default by the other.',
        );
    }
}
