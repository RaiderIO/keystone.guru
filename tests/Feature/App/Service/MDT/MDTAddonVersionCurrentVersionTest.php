<?php

namespace Tests\Feature\App\Service\MDT;

use App\Service\MDT\MDTAddonVersionService;
use App\Service\MDT\MDTAddonVersionServiceInterface;
use Composer\InstalledVersions;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

/**
 * Guards that an MDT package bump moves its three version sources together: the installed composer package,
 * config('keystoneguru.mdt.version') (stamped onto every imported mapping version) and the committed
 * addonVersion => release-date map that resolves imported MDT strings to a mapping version.
 */
#[Group('MDT')]
#[Group('MDTAddonVersion')]
final class MDTAddonVersionCurrentVersionTest extends PublicTestCase
{
    private const PACKAGE_NAME = 'nnoggie/mythicdungeontools';

    private const DATA_PATH = 'data/mdt/addon_versions.json';

    #[Test]
    public function getCurrentAddonVersion_givenInstalledPackage_returnsInstalledPackageAddonVersion(): void
    {
        // Arrange
        $installedVersion = InstalledVersions::getPrettyVersion(self::PACKAGE_NAME);
        $this->assertNotNull($installedVersion);

        /** @var MDTAddonVersionServiceInterface $mdtAddonVersionService */
        $mdtAddonVersionService = app(MDTAddonVersionServiceInterface::class);

        // Act
        $currentAddonVersion = $mdtAddonVersionService->getCurrentAddonVersion();

        // Assert
        $this->assertSame(
            MDTAddonVersionService::versionStringToAddonVersion($installedVersion),
            $currentAddonVersion,
            sprintf(
                'keystoneguru.mdt.version is %s but %s %s is installed',
                config('keystoneguru.mdt.version'),
                self::PACKAGE_NAME,
                $installedVersion,
            ),
        );
    }

    #[Test]
    public function getCurrentAddonVersion_givenCommittedAddonVersionMap_isPresentInMap(): void
    {
        // Arrange
        /** @var array<string, string> $releaseDatesByAddonVersion */
        $releaseDatesByAddonVersion = json_decode(file_get_contents(database_path(self::DATA_PATH)), true);

        /** @var MDTAddonVersionServiceInterface $mdtAddonVersionService */
        $mdtAddonVersionService = app(MDTAddonVersionServiceInterface::class);

        // Act
        $currentAddonVersion = $mdtAddonVersionService->getCurrentAddonVersion();

        // Assert
        $this->assertArrayHasKey(
            (string)$currentAddonVersion,
            $releaseDatesByAddonVersion,
            sprintf('Addon version %d is missing from %s - run mdt:syncaddonversions --refresh', $currentAddonVersion, self::DATA_PATH),
        );
    }
}
