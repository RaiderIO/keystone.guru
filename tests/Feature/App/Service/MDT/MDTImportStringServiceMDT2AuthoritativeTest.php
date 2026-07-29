<?php

namespace Tests\Feature\App\Service\MDT;

use App\Models\MDTImport;
use App\Service\MDT\MDTImportStringServiceInterface;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;

/**
 * Authoritative real-world coverage for #3735: a genuine `!~MDT2~` string exported by the actual
 * MDT 6.2 addon in the WoW client, as opposed to the self-generated fixture in
 * MDTImportStringServiceMDT2Test (which proves format equivalence but was produced by our own
 * encoder, so it cannot catch a mismatch between our assumptions and the real client output -
 * e.g. the exact deflate variant or Blizzard's CBOR type choices).
 *
 * Slot-in procedure once a real 12.1 PTR export string is available:
 * 1. Commit it (without trailing newline) as Fixtures/mdt_import_v<addonVersion>_<dungeonslug>.txt
 * 2. Point the FIXTURE constant at it and adjust the dungeon/addonVersion expectations below
 *
 * Until then these tests skip - and the MR for #3735 stays draft.
 */
#[Group('UsesLua')]
#[Group('MDTImportStringService')]
#[Group('MDT2Codec')]
final class MDTImportStringServiceMDT2AuthoritativeTest extends MDTImportStringServiceTestBase
{
    private const FIXTURE = __DIR__ . '/Fixtures/mdt_import_v620_real_ptr_string.txt';

    #[Test]
    public function getDecoded_givenRealMdt2String_returnsArray(): void
    {
        // Arrange
        $this->skipUnlessFixtureExists();

        // Act
        $decoded = app()->make(MDTImportStringServiceInterface::class)
            ->setEncodedString(file_get_contents(self::FIXTURE))
            ->getDecoded();

        // Assert
        $this->assertIsArray($decoded);
        $this->assertArrayHasKey('value', $decoded);
        $this->assertArrayHasKey('objects', $decoded);
        $this->assertArrayHasKey('addonVersion', $decoded);
    }

    #[Test]
    public function getDungeonRoute_givenRealMdt2String_returnsDungeonRouteWithPulls(): void
    {
        // Arrange
        $this->skipUnlessFixtureExists();

        try {
            // Act
            $dungeonRoute = $this->importStringToDungeonRoute(file_get_contents(self::FIXTURE));

            // Assert
            $this->assertNotEmpty($dungeonRoute->killZones);
        } finally {
            MDTImport::query()->where('import_string', file_get_contents(self::FIXTURE))->delete();
        }
    }

    private function skipUnlessFixtureExists(): void
    {
        if (!file_exists(self::FIXTURE)) {
            $this->markTestSkipped('Awaiting a real MDT 6.2 PTR export string - see #3735 for the slot-in procedure.');
        }
    }
}
