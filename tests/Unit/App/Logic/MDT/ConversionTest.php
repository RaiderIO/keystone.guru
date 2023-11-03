<?php

namespace Tests\Unit\App\Logic\MDT;

use App\Logic\MDT\Conversion;
use App\Models\Dungeon;
use App\Models\Expansion;
use Tests\TestCase;

class ConversionTest extends TestCase
{
    /**
     * A basic test example.
     *
     * @test
     * @return void
     * @dataProvider checkGetExpansionName_GivenDungeonKey_ShouldBeCorrect_Provider
     * @group
     */
    public function checkGetExpansionName_GivenDungeonKey_ShouldBeCorrect(string $dungeonKey, string $expectedExpansionKey)
    {
        // Test
        $expansionKey = Conversion::getExpansionName($dungeonKey);

        // Assert
        $this->assertEquals($expansionKey, $expectedExpansionKey);
    }

    /**
     * @return array
     */
    public function checkGetExpansionName_GivenDungeonKey_ShouldBeCorrect_Provider(): array
    {
        $expansions = [
            Expansion::EXPANSION_CATACLYSM,
            Expansion::EXPANSION_MOP,
            Expansion::EXPANSION_LEGION,
            Expansion::EXPANSION_BFA,
            Expansion::EXPANSION_SHADOWLANDS,
            Expansion::EXPANSION_DRAGONFLIGHT
        ];

        $result = [];
        foreach ($expansions as $expansion) {
            foreach (Conversion::DUNGEON_NAME_MAPPING[$expansion] as $dungeonKey => $value) {
                $result[] = [$dungeonKey, $expansion];
            }
        }
        return $result;
    }
}
