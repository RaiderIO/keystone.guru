<?php

namespace Tests\Feature\Controller\Api\V1\APICombatLogController\CombatLogRoute;

use App\Models\Affix;
use Tests\Feature\Controller\Api\V1\APICombatLogController\APICombatLogControllerTestBase;

abstract class APICombatLogControllerCombatLogRouteTestBase extends APICombatLogControllerTestBase
{
    protected const FIXTURES_ROOT_DIR = '../../';

    protected function validateResponseStaticData(array $response): void
    {
        // Author
        $this->assertEquals('Admin', $response['data']['author']['name']);
        $this->assertNotEmpty($response['data']['author']['links']);
        $this->assertTrue($this->isValidUrl($response['data']['author']['links']['view']));

        // Links
        $this->assertNotEmpty($response['data']['links']);
        $this->assertTrue($this->isValidUrl($response['data']['links']['view']));
        $this->assertTrue($this->isValidUrl($response['data']['links']['edit']));
        $this->assertTrue($this->isValidUrl($response['data']['links']['embed']));

        foreach ($response['data']['links']['thumbnails'] as $thumbnail) {
            $this->assertTrue($this->isValidUrl($thumbnail));
        }
    }

    protected function validateDungeon(array $response): void
    {
        $this->assertEquals($this->dungeon->id, $response['data']['dungeonId']);
        $this->assertEquals(__($this->dungeon->name, [], 'en'), $response['data']['title']);
    }

    protected function validatePulls(array $responseArr, int $pulls, int $enemyForces): void
    {
        $this->assertCount($pulls, $responseArr['data']['pulls']);
        $this->assertEquals($enemyForces, $responseArr['data']['enemyForces']);
        $this->assertEquals($this->dungeon->getCurrentMappingVersion()->enemy_forces_required, $responseArr['data']['enemyForcesRequired']);
    }

    protected function validateSpells(array $responseArr, int $spellCount, array $mustHaveSpells = []): void
    {
        $responseSpellCount = 0;
        foreach ($responseArr['data']['pulls'] as $pull) {
            $responseSpellCount += count($pull['spells']);

            foreach($pull['spells'] as $spellId) {
                // Remove $spellId from $mustHaveSpells
                $key = array_search($spellId, $mustHaveSpells);
                if ($key !== false) {
                    unset($mustHaveSpells[$key]);
                }
            }
        }

        $this->assertEquals($spellCount, $responseSpellCount);
        $this->assertEmpty($mustHaveSpells, implode(', ', $mustHaveSpells));
    }

    protected function validateAffixes(array $responseArr, string ...$affixes): void
    {
        // AffixGroups
        $this->assertNotEmpty($responseArr['data']['affixGroups']);
        $this->assertNotEmpty($responseArr['data']['affixGroups'][0]['affixes'][0]);

        $validAffixIds = array_map(function (array $affix) {
            return $affix['id'];
        }, $responseArr['data']['affixGroups'][0]['affixes']);

        foreach (Affix::whereIn('key', $affixes)->get() as $affix) {
            /** @var Affix $affix */
            $this->assertContains($affix->affix_id, $validAffixIds);
        }
    }
}
