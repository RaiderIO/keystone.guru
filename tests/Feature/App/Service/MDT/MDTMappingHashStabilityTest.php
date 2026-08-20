<?php

namespace Tests\Feature\App\Service\MDT;

use App\Models\Dungeon;
use App\Service\MDT\MDTMappingImportServiceInterface;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

/**
 * #4110: MDTMappingImportService::getMDTMappingHash() json_encode()'d the raw Lua-derived arrays without
 * normalizing key order first. Lua's pairs()/hash-part iteration order over a table's string keys is not
 * guaranteed stable across separate VM instances - and MDTDungeon::getLua() builds a fresh VM per call - so
 * any dungeon whose POI `info` sub-table carries 2+ keys (e.g. a genericItem POI with both `atlas` and
 * `spellId`/`texture`) could hash differently between two calls for the exact same underlying data, producing
 * spurious "mapping changed" detections and duplicate MappingVersion rows on every mdt:importmapping run.
 * the_blinding_vale and murder_row are the two dungeons that reproduced this in #4108's investigation.
 */
#[Group('UsesLua')]
#[Group('MDT')]
final class MDTMappingHashStabilityTest extends PublicTestCase
{
    #[Test]
    public function getMDTMappingHash_givenDungeonWithMultiKeyPOIInfoTable_isStableAcrossRepeatedCalls(): void
    {
        // Arrange
        $mappingImportService = $this->app->make(MDTMappingImportServiceInterface::class);

        /** @var Dungeon $dungeon */
        $dungeon = Dungeon::query()->where('key', 'the_blinding_vale')->firstOrFail();

        // Act
        $firstHash  = $mappingImportService->getMDTMappingHash($dungeon);
        $secondHash = $mappingImportService->getMDTMappingHash($dungeon);
        $thirdHash  = $mappingImportService->getMDTMappingHash($dungeon);

        // Assert
        $this->assertSame($firstHash, $secondHash, 'The MDT mapping hash must be stable across repeated calls for the same, unchanged dungeon.');
        $this->assertSame($firstHash, $thirdHash, 'The MDT mapping hash must be stable across repeated calls for the same, unchanged dungeon.');
    }
}
