<?php

namespace Tests\Feature\Controller\Api\V1\APICombatLogController\CreateRoute;

use App\Models\Affix;
use Tests\Feature\Controller\Api\V1\APICombatLogController\APICombatLogControllerTestBase;

abstract class APICombatLogControllerCreateRouteTestBase extends APICombatLogControllerTestBase
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

        $this->assertNotEmpty($response['data']['links']['thumbnails']);
        foreach ($response['data']['links']['thumbnails'] as $thumbnail) {
            $this->assertTrue($this->isValidUrl($thumbnail));
        }
    }

    protected function validateDungeon(array $response): void
    {
        $this->assertEquals($this->dungeon->id, $response['data']['dungeon_id']);
        $this->assertEquals(__($this->dungeon->name, [], 'en'), $response['data']['title']);
    }

    protected function validatePulls(array $responseArr, int $pulls, int $enemyForces): void
    {
        $this->assertEquals($pulls, $responseArr['data']['pulls']);
        $this->assertEquals($enemyForces, $responseArr['data']['enemy_forces']);
        $this->assertEquals($this->dungeon->currentMappingVersion->enemy_forces_required, $responseArr['data']['enemy_forces_required']);
    }

    protected function validateAffixes(array $responseArr, string ...$affixes): void
    {
        // AffixGroups
        $this->assertNotEmpty($responseArr['data']['affix_groups']);
        $this->assertNotEmpty($responseArr['data']['affix_groups'][0]['affixes'][0]);

        $validAffixIds = array_map(function (array $affix) {
            return $affix['id'];
        }, $responseArr['data']['affix_groups'][0]['affixes']);

        foreach (Affix::whereIn('key', $affixes)->get() as $affix) {
            /** @var Affix $affix */
            $this->assertContains($affix->affix_id, $validAffixIds);
        }
    }
}
