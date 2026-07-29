<?php

namespace Tests\Feature\App\Service\MDT;

use App\Logic\MDT\Conversion;
use App\Logic\MDT\IO\MDT2Codec;
use App\Models\Dungeon;
use App\Models\MDTImport;
use App\Service\Mapping\MappingServiceInterface;
use App\Service\MDT\MDTImportStringServiceInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Artisan;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;

/**
 * Authoritative real-world coverage for #3735: two genuine `!~MDT2~` strings exported by the actual
 * MDT addon running in the WoW 12.1 PTR client, as opposed to the self-generated fixture in
 * MDTImportStringServiceMDT2Test - that one proves the two formats carry identical content, but it
 * was produced by our own encoder, so it cannot catch a mismatch between our assumptions and what
 * the real client emits.
 *
 * What these fixtures settle that a self-generated one cannot:
 *
 * - `C_EncodingUtil.CompressString(..., Enum.CompressionMethod.Deflate)` emits a RAW deflate stream
 *   (`gzinflate`), not a zlib container (`gzuncompress`) - open question 1 on the issue.
 * - Blizzard's `SerializeCBOR` really does emit Lua strings as CBOR byte strings (major type 2) and
 *   not text strings (major type 3), which is what MDT2Codec::encode() mirrors for the eventual
 *   export flip (#3740).
 * - Neither `week` nor `addonVersion` survives into an MDT 6.2 export. `week` is explicitly nilled
 *   by the addon (Presets.lua), and `addonVersion` is derived as
 *   `tonumber(("6.2.0-alpha3"):gsub("%.", ""))`, which is nil on any alpha/PTR build - a released
 *   6.2.0 yields 620. Both are addon-side changes rather than format changes (a legacy-format
 *   string exported by MDT 6.2 would lack them too) and both already fall back gracefully.
 */
#[Group('UsesLua')]
#[Group('MDTImportStringService')]
#[Group('MDT2Codec')]
final class MDTImportStringServiceMDT2AuthoritativeTest extends MDTImportStringServiceTestBase
{
    private const FIXTURE_KINGS_REST = __DIR__ . '/Fixtures/mdt_import_mdt2_real_kingsrest.txt';

    private const FIXTURE_RUBY_LIFE_POOLS = __DIR__ . '/Fixtures/mdt_import_mdt2_real_rubylifepools.txt';

    #[Test]
    #[DataProvider('realMdt2String_Provider')]
    public function getDecoded_givenRealMdt2String_returnsPreset(string $fixture, string $expectedText, string $expectedDungeonKey): void
    {
        // Arrange
        $encodedString = self::readFixture($fixture);

        // Act
        $decoded = app()->make(MDTImportStringServiceInterface::class)
            ->setEncodedString($encodedString)
            ->getDecoded();

        // Assert
        $this->assertIsArray($decoded);
        $this->assertSame($expectedText, $decoded['text']);
        $this->assertArrayHasKey('objects', $decoded);
        $this->assertNotEmpty($decoded['value']['pulls']);
        $this->assertSame($expectedDungeonKey, $this->dungeonFor($decoded)->key);
    }

    #[Test]
    #[DataProvider('realMdt2String_Provider')]
    public function decode_givenRealMdt2Payload_usesRawDeflateAndByteStrings(string $fixture): void
    {
        // Arrange - peel the encoding layers by hand so the assertions are about the real client's
        // output rather than about MDT2Codec agreeing with itself
        $base64 = substr(self::readFixture($fixture), strlen(MDT2Codec::PREFIX));

        // Act
        $compressed = base64_decode($base64, true);
        $cbor       = @gzinflate($compressed);

        // Assert - raw deflate, not a zlib container (settles open question 1 on #3735)
        $this->assertIsString($compressed);
        $this->assertIsString($cbor, 'A real MDT 6.2 payload must inflate as a raw deflate stream');
        $this->assertFalse(@gzuncompress($compressed), 'A real MDT 6.2 payload must NOT be zlib-wrapped');

        // Assert - the CBOR root is a map, and its first key is a byte string (major type 2), which
        // is what MDT2Codec::encode() emits for Lua strings. Byte 1 is only the first key when the
        // map's pair count fits in the initial byte's additional-information field (< 24), so guard
        // that too - a future fixture with 24+ top-level keys would otherwise assert on a length
        // byte and silently stop proving anything.
        $this->assertSame(0b101, ord($cbor[0]) >> 5, 'The CBOR root must be a map (major type 5)');
        $this->assertLessThan(24, ord($cbor[0]) & 0b11111, 'The root map must pack its pair count into the initial byte');
        $this->assertSame(0b010, ord($cbor[1]) >> 5, 'Blizzard must serialize Lua strings as CBOR byte strings (major type 2)');
    }

    #[Test]
    #[DataProvider('realMdt2String_Provider')]
    public function getDecoded_givenRealMdt2String_carriesNoWeekOrAddonVersion(string $fixture): void
    {
        // Act
        $decoded = app()->make(MDTImportStringServiceInterface::class)
            ->setEncodedString(self::readFixture($fixture))
            ->getDecoded();

        // Assert - MDT 6.2 drops both; see the class docblock for why. Documented so a future
        // release build that DOES carry addonVersion (620) shows up as a failure here rather than
        // silently changing which mapping version imports attach to.
        $this->assertIsArray($decoded);
        $this->assertArrayNotHasKey('week', $decoded);
        $this->assertArrayNotHasKey('addonVersion', $decoded);
    }

    #[Test]
    #[DataProvider('realMdt2String_Provider')]
    public function getMappingVersionForMdtAddonVersion_givenRealMdt2String_fallsBackToCurrent(string $fixture): void
    {
        // Arrange - a string without addonVersion must land on the dungeon's current mapping version
        $decoded = app()->make(MDTImportStringServiceInterface::class)
            ->setEncodedString(self::readFixture($fixture))
            ->getDecoded();
        $dungeon               = $this->dungeonFor($decoded);
        $currentMappingVersion = $dungeon->getCurrentMappingVersion();

        // Act
        $mappingVersion = app()->make(MappingServiceInterface::class)
            ->getMappingVersionForMdtAddonVersion($dungeon, $decoded['addonVersion'] ?? null);

        // Assert
        $this->assertNotNull($currentMappingVersion);
        $this->assertNotNull($mappingVersion);
        $this->assertSame($currentMappingVersion->id, $mappingVersion->id);
    }

    #[Test]
    #[DataProvider('realMdt2String_Provider')]
    public function getDungeonRoute_givenRealMdt2String_returnsRouteWithPulls(string $fixture, string $expectedText, string $expectedDungeonKey): void
    {
        // Arrange
        $encodedString = self::readFixture($fixture);

        try {
            // Act
            $dungeonRoute = $this->importStringToDungeonRoute($encodedString);
            $dungeonRoute->load(['killZones.killZoneEnemies']);

            // Assert
            $this->assertSame($expectedDungeonKey, $dungeonRoute->dungeon->key);
            $this->assertNotEmpty($dungeonRoute->killZones);
        } finally {
            MDTImport::query()->where('import_string', $encodedString)->delete();
        }
    }

    #[Test]
    #[DataProvider('realMdt2String_Provider')]
    public function getDetails_givenRealMdt2String_matchesTheSamePresetInTheLegacyFormat(string $fixture): void
    {
        // Arrange - re-encode the real preset into the legacy format so both formats can be run
        // through the identical pipeline. Any import warning a real string produces is then proven
        // to come from the mapping rather than from the format.
        $mdt2String          = self::readFixture($fixture);
        $importStringService = app()->make(MDTImportStringServiceInterface::class);

        Artisan::call('mdt:encode', ['string' => json_encode($importStringService->setEncodedString($mdt2String)->getDecoded())]);
        $legacyString = trim(Artisan::output());

        try {
            // Act
            $mdt2Details   = $importStringService->setEncodedString($mdt2String)->getDetails(new Collection(), new Collection());
            $legacyDetails = $importStringService->setEncodedString($legacyString)->getDetails(new Collection(), new Collection());

            $mdt2Route   = $this->importStringToDungeonRoute($mdt2String);
            $legacyRoute = $this->importStringToDungeonRoute($legacyString);

            $mdt2Route->load(['killZones.killZoneEnemies']);
            $legacyRoute->load(['killZones.killZoneEnemies']);

            // Assert - equivalence only, never absolute counts: those move with every mapping change
            $this->assertStringStartsWith('!', $legacyString);
            $this->assertStringStartsNotWith(MDT2Codec::PREFIX, $legacyString);
            $this->assertEquals($legacyDetails->toArray(), $mdt2Details->toArray());
            $this->assertSame($legacyRoute->dungeon_id, $mdt2Route->dungeon_id);
            $this->assertNotEmpty($mdt2Route->killZones, 'The fixture must have pulls or this test is vacuous');
            $this->assertEquals(
                $legacyRoute->killZones->map(fn($killZone) => $killZone->killZoneEnemies->count()),
                $mdt2Route->killZones->map(fn($killZone) => $killZone->killZoneEnemies->count()),
            );
        } finally {
            MDTImport::query()->whereIn('import_string', [$mdt2String, $legacyString])->delete();
        }
    }

    /**
     * @return array<string, array{string, string, string}>
     */
    public static function realMdt2String_Provider(): array
    {
        return [
            "Kings' Rest"     => [self::FIXTURE_KINGS_REST, 'KR+15 A Week', 'kingsrest'],
            'Ruby Life Pools' => [self::FIXTURE_RUBY_LIFE_POOLS, 'Basic RLP by Dratnos', 'rubylifepools'],
        ];
    }

    private static function readFixture(string $fixture): string
    {
        return file_get_contents($fixture);
    }

    /**
     * @param array<string, mixed> $decoded
     */
    private function dungeonFor(array $decoded): Dungeon
    {
        return Conversion::convertMDTDungeonIDToDungeon((int)$decoded['value']['currentDungeonIdx']);
    }
}
