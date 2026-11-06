<?php

namespace Tests\Unit\App\Vendor\SemVer;

use App\Vendor\SemVer\Version;
use PHLAK\SemVer\Exceptions\InvalidVersionException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

#[Group('SemVer')]
final class VersionTest extends TestCase
{
    #[Test]
    #[DataProvider('parse_givenVersionString_returnsAppVersion_provider')]
    public function parse_givenVersionString_returnsAppVersion(
        string  $versionString,
        int     $expectedMajor,
        int     $expectedMinor,
        int     $expectedPatch,
        ?string $expectedPreRelease,
        ?string $expectedBuild,
    ): void {
        // Arrange & Act
        $version = Version::parse($versionString);

        // Assert
        $this->assertInstanceOf(Version::class, $version);
        $this->assertSame($expectedMajor, $version->getMajor());
        $this->assertSame($expectedMinor, $version->getMinor());
        $this->assertSame($expectedPatch, $version->getPatch());
        $this->assertSame($expectedPreRelease, $version->getPreRelease());
        $this->assertSame($expectedBuild, $version->getBuild());
    }

    /**
     * @return array<string, array{string, int, int, int, ?string, ?string}>
     */
    public static function parse_givenVersionString_returnsAppVersion_provider(): array
    {
        return [
            'full release tag'           => ['v14.2.3', 14, 2, 3, null, null],
            'major only'                 => ['v1', 1, 0, 0, null, null],
            'major and minor'            => ['v1.2', 1, 2, 0, null, null],
            'no v prefix'                => ['3.4.5', 3, 4, 5, null, null],
            'incomplete with prerelease' => ['v1-beta.4', 1, 0, 0, 'beta.4', null],
            'incomplete with build'      => ['v1.3+007', 1, 3, 0, null, '007'],
        ];
    }

    #[Test]
    public function parse_givenNonVersionString_throwsInvalidVersionException(): void
    {
        // Arrange
        $this->expectException(InvalidVersionException::class);

        // Act
        Version::parse('release-14');
    }
}
