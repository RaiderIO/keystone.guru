<?php

namespace App\Logic\MDT;

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
        $result = [];
        foreach (Dungeon::ALL_LEGION as $dungeonKey) {
            $result[] = [$dungeonKey, Expansion::EXPANSION_LEGION];
        }
        foreach (Dungeon::ALL_BFA as $dungeonKey) {
            $result[] = [$dungeonKey, Expansion::EXPANSION_BFA];
        }
        foreach (Dungeon::ALL_SHADOWLANDS as $dungeonKey) {
            $result[] = [$dungeonKey, Expansion::EXPANSION_SHADOWLANDS];
        }
        return $result;
    }
}
