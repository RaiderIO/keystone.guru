<?php

namespace Tests\Feature\Controller\Api\V1\APICombatLogController\CombatLogRoute;

use App\Models\Affix;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Mapping\MappingVersion;
use App\Models\Npc\Npc;
use Tests\Feature\Controller\Api\V1\APICombatLogController\APICombatLogControllerTestBase;

abstract class APICombatLogControllerCombatLogRouteTestBase extends APICombatLogControllerTestBase
{
    protected const FIXTURES_ROOT_DIR = '../../';

    /**
     * @param array<string, mixed> $response
     */
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

    /**
     * @param array<string, mixed> $response
     */
    protected function validateDungeon(array $response): void
    {
        $this->assertEquals($this->dungeon->id, $response['data']['dungeonId']);
        $this->assertEquals(__($this->dungeon->name, [], 'en_US'), $response['data']['title']);
    }

    /**
     * @param array<string, mixed> $responseArr
     */
    protected function validatePulls(array $responseArr, int $pulls, int $enemyForces): void
    {
        $this->assertCount($pulls, $responseArr['data']['pulls']);
        $this->assertEquals($enemyForces, $responseArr['data']['enemyForces']);
        /** @var MappingVersion|null $mappingVersion */
        $mappingVersion = MappingVersion::where('version', $responseArr['data']['mappingVersion'])
            ->where('dungeon_id', $this->dungeon->id)
            ->first();
        $this->assertEquals($mappingVersion->enemy_forces_required, $responseArr['data']['enemyForcesRequired']);
    }

    /**
     * @param array<string, mixed> $responseArr
     * @param array<int, int>      $mustHaveSpells
     */
    protected function validateSpells(array $responseArr, int $spellCount, array $mustHaveSpells = []): void
    {
        $responseSpellCount = 0;
        foreach ($responseArr['data']['pulls'] as $pull) {
            $responseSpellCount += count($pull['spells']);

            foreach ($pull['spells'] as $spellId) {
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

    /**
     * @param array<string, mixed> $responseArr
     */
    protected function validateAffixes(array $responseArr, string ...$affixes): void
    {
        // AffixGroups
        if (!empty($affixes)) {
            $this->assertNotEmpty($responseArr['data']['affixGroups']);
            $this->assertNotEmpty($responseArr['data']['affixGroups'][0]['affixes'][0]);

            $validAffixIds = array_map(fn(array $affix) => $affix['id'], $responseArr['data']['affixGroups'][0]['affixes']);

            foreach (Affix::whereIn('key', $affixes)->get() as $affix) {
                /** @var Affix $affix */
                $this->assertContains($affix->affix_id, $validAffixIds, sprintf('Affix with key %s and id %d not found in response [%s]', $affix->key, $affix->affix_id, implode(', ', $validAffixIds)));
            }
        }
    }

    /**
     * Every boss the party actually killed must end up in a pull. A boss that is silently dropped - because its
     * mapped position is out of range of where it was killed, or because it resolved onto the wrong floor - is the
     * failure mode a hardcoded pull/enemy-forces count cannot see, so it is asserted separately from those numbers.
     *
     * @param array<string, mixed> $postBody
     * @param array<string, mixed> $responseArr
     */
    protected function validateBossesResolved(array $postBody, array $responseArr): void
    {
        /** @var array<int, array<string, mixed>> $killedNpcs */
        $killedNpcs   = $postBody['npcs'];
        $killedNpcIds = array_values(array_unique(array_column($killedNpcs, 'npcId')));

        $bossNpcIds = Npc::query()
            ->whereIn('id', $killedNpcIds)
            ->get()
            ->filter(static fn(Npc $npc): bool => $npc->isBoss())
            ->pluck('id')
            ->all();

        $this->assertNotEmpty($bossNpcIds, 'The combat log contains no boss kills at all');

        /** @var array<int, array<string, mixed>> $pulls */
        $pulls          = $responseArr['data']['pulls'];
        $resolvedNpcIds = [];

        foreach ($pulls as $pull) {
            /** @var array<int, array<string, mixed>> $enemies */
            $enemies        = $pull['enemies'];
            $resolvedNpcIds = array_merge($resolvedNpcIds, array_column($enemies, 'npcId'));
        }

        foreach ($bossNpcIds as $bossNpcId) {
            $this->assertContains(
                $bossNpcId,
                $resolvedNpcIds,
                sprintf('Boss NPC %d was killed in the combat log but is not part of any pull', $bossNpcId),
            );
        }
    }

    /**
     * The route the test just created is persisted, and the test database is not rolled back between tests.
     *
     * @param array<string, mixed> $responseArr
     */
    protected function deleteDungeonRoute(array $responseArr): void
    {
        DungeonRoute::where('public_key', $responseArr['data']['publicKey'])->first()?->delete();
    }
}
