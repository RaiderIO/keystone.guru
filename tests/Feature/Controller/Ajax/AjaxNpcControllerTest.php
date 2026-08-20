<?php

namespace Tests\Feature\Controller\Ajax;

use App\Models\Dungeon;
use App\Models\Enemy;
use App\Models\Faction;
use App\Models\Mapping\MappingVersion;
use App\Models\Npc\Npc;
use App\Models\Translation\Translation;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Traits\ProvidesDungeon;
use Tests\TestCases\AjaxPublicTestCase;

#[Group('Controller')]
#[Group('Npc')]
final class AjaxNpcControllerTest extends AjaxPublicTestCase
{
    use ProvidesDungeon;

    private const int NPC_ID = 999999901;

    #[Test]
    public function get_givenNpcHasEnemiesOnAnOlderMappingVersion_onlyCountsEnemiesOnTheLatestMappingVersion(): void
    {
        // Arrange
        [$dungeon, $existingMappingVersion] = $this->findDungeon();
        $floorId                            = $dungeon->floors()->where('facade', false)->value('id');

        $newMappingVersion = null;
        $npc               = null;
        $translation       = null;

        try {
            $newMappingVersion = $this->createNewerMappingVersion($dungeon, $existingMappingVersion);

            $npc = Npc::create([
                'id'                => self::NPC_ID,
                'classification_id' => 1,
                'npc_type_id'       => 1,
                'npc_class_id'      => 1,
                'display_id'        => null,
                'name'              => sprintf('npc.%d.name', self::NPC_ID),
                'aggressiveness'    => Npc::AGGRESSIVENESS_AGGRESSIVE,
                'dangerous'         => 0,
                'truesight'         => 0,
            ]);
            $npc->dungeons()->attach($dungeon->id);
            // The admin NPC datatable filters on the translated name (see NameColumnHandler), so a
            // test NPC without a translation is silently excluded from every result page.
            $translation = Translation::create([
                'locale'      => 'en_US',
                'key'         => $npc->name,
                'translation' => 'Test NPC for enemy count',
            ]);

            // Two enemies on the older mapping version - these must not be counted
            $this->createEnemy($existingMappingVersion->id, $floorId);
            $this->createEnemy($existingMappingVersion->id, $floorId);
            // One enemy on the latest mapping version - this is the only one that should be counted
            $this->createEnemy($newMappingVersion->id, $floorId);

            // Act
            $response = $this->get(sprintf('/ajax/admin/npc?%s', http_build_query([
                'draw'    => 1,
                'start'   => 0,
                'length'  => 100000,
                'columns' => [
                    [
                        'data'       => 0,
                        'name'       => 'name',
                        'searchable' => 'true',
                        'orderable'  => 'true',
                        'search'     => ['value' => '', 'regex' => 'false'],
                    ],
                ],
                'search' => ['value' => '', 'regex' => 'false'],
            ])));

            // Assert
            $response->assertOk();

            /** @var array<int, array<string, mixed>> $data */
            $data = $response->json('data');
            $row  = null;
            foreach ($data as $candidate) {
                if ($candidate['id'] === self::NPC_ID) {
                    $row = $candidate;

                    break;
                }
            }
            $this->assertNotNull($row, 'The test NPC should be present in the admin NPC datatable result.');
            $this->assertSame(1, $row['enemy_count'], 'Enemy count must only reflect the latest mapping version, not every mapping version ever created.');
        } finally {
            Enemy::where('npc_id', self::NPC_ID)->delete();
            if ($npc !== null) {
                $npc->dungeons()->detach($dungeon->id);
                $npc->delete();
            }
            if ($translation !== null) {
                $translation->delete();
            }
            if ($newMappingVersion !== null) {
                $newMappingVersion->delete();
            }
        }
    }

    private function createEnemy(int $mappingVersionId, int $floorId): Enemy
    {
        return Enemy::create([
            'mapping_version_id' => $mappingVersionId,
            'floor_id'           => $floorId,
            'npc_id'             => self::NPC_ID,
            'mdt_id'             => null,
            'faction'            => Faction::ALL[Faction::FACTION_UNSPECIFIED],
            'lat'                => -100.0,
            'lng'                => 100.0,
        ]);
    }

    private function createNewerMappingVersion(Dungeon $dungeon, MappingVersion $existing): MappingVersion
    {
        return MappingVersion::create([
            'game_version_id'                 => $existing->game_version_id,
            'dungeon_id'                      => $dungeon->id,
            'version'                         => $existing->version + 1000,
            'enemy_forces_required'           => $existing->enemy_forces_required,
            'enemy_forces_required_teeming'   => $existing->enemy_forces_required_teeming,
            'enemy_forces_shrouded'           => $existing->enemy_forces_shrouded,
            'enemy_forces_shrouded_zul_gamux' => $existing->enemy_forces_shrouded_zul_gamux,
            'timer_max_seconds'               => $existing->timer_max_seconds,
            'facade_enabled'                  => false,
        ]);
    }
}
