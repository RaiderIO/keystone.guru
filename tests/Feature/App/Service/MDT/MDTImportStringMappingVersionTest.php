<?php

namespace Tests\Feature\App\Service\MDT;

use App\Logic\MDT\Conversion;
use App\Models\Dungeon;
use App\Service\MDT\MDTAddonVersionServiceInterface;
use App\Service\MDT\MDTImportStringServiceInterface;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

/**
 * Integration coverage for #3380: a real MDT export string carries an `addonVersion`, and the importer
 * uses it to attach the route to the mapping version of that MDT era (rather than always the newest),
 * so an imported older route is flagged as outdated and offered an upgrade.
 *
 * The committed fixture is a genuine production import string (mdt_imports #131618) built with MDT
 * v5.0.7 (addonVersion 507, released 2024-08-26) for the dungeon "Mists of Tirna Scithe".
 */
#[Group('UsesLua')]
#[Group('MDT')]
#[Group('MDTAddonVersion')]
final class MDTImportStringMappingVersionTest extends PublicTestCase
{
    private const FIXTURE = __DIR__ . '/Fixtures/mdt_import_v507_mistsoftirnescithe.txt';

    #[Test]
    public function getDecoded_givenRealMdtString_exposesAddonVersionPresentInMap(): void
    {
        // Arrange
        $encodedString = file_get_contents(self::FIXTURE);

        // Act
        $decoded = app()->make(MDTImportStringServiceInterface::class)
            ->setEncodedString($encodedString)
            ->getDecoded();

        // Assert - the string carries the expected addonVersion, and the committed map can resolve it (data-map guard).
        // Lua decodes numbers as floats, hence the cast (the importer casts to int too).
        $this->assertSame(507, (int)($decoded['addonVersion'] ?? 0));
        $this->assertNotNull(
            app()->make(MDTAddonVersionServiceInterface::class)->getReleaseDate(507),
            'Every addonVersion used in fixtures must exist in database/data/mdt/addon_versions.json.',
        );
    }

    #[Test]
    public function getMappingVersionForMdtAddonVersion_givenRealOldMdtString_selectsNonLatestMappingVersion(): void
    {
        // Arrange
        $decoded = app()->make(MDTImportStringServiceInterface::class)
            ->setEncodedString(file_get_contents(self::FIXTURE))
            ->getDecoded();

        /** @var Dungeon $dungeon */
        $dungeon = Conversion::convertMDTDungeonIDToDungeon($decoded['value']['currentDungeonIdx']);

        // Act - this is exactly the resolution the importer performs at assignment time.
        $selected = $dungeon->getMappingVersionForMdtAddonVersion(
            isset($decoded['addonVersion']) ? (int)$decoded['addonVersion'] : null,
        );

        // Assert - the old string lands on a historical mapping version, not the current one.
        $this->assertNotNull($selected);
        $this->assertFalse(
            $selected->isLatestForDungeon(),
            'An MDT string from an older addon version must resolve to a non-latest mapping version.',
        );
        $this->assertLessThan(
            $dungeon->getCurrentMappingVersion()->version,
            $selected->version,
            'The selected mapping version must be older than the current one.',
        );
    }
}
