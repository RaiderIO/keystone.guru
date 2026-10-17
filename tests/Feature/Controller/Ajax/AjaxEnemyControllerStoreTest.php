<?php

namespace Tests\Feature\Controller\Ajax;

use App\Models\DungeonDifficulty;
use App\Models\Enemy;
use App\Models\Laratrust\Role;
use App\Models\Mapping\MappingChangeLog;
use App\Models\Mapping\MappingVersion;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\AjaxPublicTestCase;

#[Group('Controller')]
#[Group('Enemy')]
final class AjaxEnemyControllerStoreTest extends AjaxPublicTestCase
{
    #[Test]
    public function store_givenLegacyActiveAurasInPayload_doesNotTouchEnemyActiveAurasTable(): void
    {
        // Arrange
        /** @var Enemy $enemy */
        $enemy = Enemy::query()->orderBy('id')->firstOrFail();
        /** @var MappingVersion $mappingVersion */
        $mappingVersion = MappingVersion::query()->findOrFail($enemy->mapping_version_id);

        $payload = [
            'floor_id'      => $enemy->floor_id,
            'npc_id'        => $enemy->npc_id,
            'faction'       => $enemy->faction,
            'required'      => (int)$enemy->required,
            'skippable'     => (int)$enemy->skippable,
            'hyper_respawn' => (int)$enemy->hyper_respawn,
            'kill_priority' => $enemy->kill_priority ?? 0,
            'lat'           => $enemy->lat,
            'lng'           => $enemy->lng,
            'active_auras'  => [1],
        ];

        $queries = [];
        DB::listen(static function ($query) use (&$queries): void {
            $queries[] = $query->sql;
        });

        $lastMappingChangeLogId = (int)MappingChangeLog::query()->max('id');

        try {
            // Act
            $response = $this->put(sprintf('/ajax/admin/mappingVersion/%d/enemy/%d', $mappingVersion->id, $enemy->id), $payload);

            // Assert
            $response->assertSuccessful();
            $response->assertJsonPath('id', $enemy->id);
            $this->assertEmpty(
                array_filter($queries, static fn(string $sql) => str_contains($sql, 'enemy_active_auras')),
                'Saving an enemy must not query enemy_active_auras',
            );
        } finally {
            MappingChangeLog::query()->where('id', '>', $lastMappingChangeLogId)->delete();
        }
    }

    #[Test]
    public function store_givenNonAdminUser_returnsForbiddenAndLeavesTheEnemyAlone(): void
    {
        // Arrange
        /** @var Enemy $enemy */
        $enemy    = Enemy::query()->whereNotNull('floor_id')->orderBy('id')->firstOrFail();
        $nonAdmin = User::factory()->create();
        $nonAdmin->addRole(Role::ROLE_USER);

        $lastMappingChangeLogId = (int)MappingChangeLog::query()->max('id');

        try {
            $this->actingAs($nonAdmin);

            // Act
            $response = $this->put(sprintf('/ajax/admin/mappingVersion/%d/enemy/%d', $enemy->mapping_version_id, $enemy->id), [
                'floor_id'      => $enemy->floor_id,
                'npc_id'        => $enemy->npc_id,
                'faction'       => $enemy->faction,
                'required'      => (int)$enemy->required,
                'skippable'     => (int)$enemy->skippable,
                'hyper_respawn' => (int)$enemy->hyper_respawn,
                'kill_priority' => $enemy->kill_priority ?? 0,
                'lat'           => $enemy->lat + 10,
                'lng'           => $enemy->lng + 10,
            ]);

            // Assert
            $response->assertForbidden();
            $this->assertEquals($enemy->lat, $enemy->fresh()->lat);
            $this->assertEquals($enemy->lng, $enemy->fresh()->lng);
        } finally {
            Enemy::query()->whereKey($enemy->id)->update(['lat' => $enemy->lat, 'lng' => $enemy->lng]);
            MappingChangeLog::query()->where('id', '>', $lastMappingChangeLogId)->delete();
            $nonAdmin->delete();
        }
    }

    #[Test]
    #[DataProvider('store_givenDungeonDifficulty_savesTheDungeonDifficulty_dataProvider')]
    public function store_givenDungeonDifficulty_savesTheDungeonDifficulty(DungeonDifficulty $dungeonDifficulty): void
    {
        // Arrange
        /** @var Enemy $enemy */
        $enemy = Enemy::query()->whereNotNull('floor_id')->orderBy('id')->firstOrFail();

        $lastMappingChangeLogId = (int)MappingChangeLog::query()->max('id');

        try {
            // Act
            $response = $this->putJson(
                sprintf('/ajax/admin/mappingVersion/%d/enemy/%d', $enemy->mapping_version_id, $enemy->id),
                $this->getStorePayload($enemy, $dungeonDifficulty->value),
            );

            // Assert
            $response->assertSuccessful();
            $this->assertSame($dungeonDifficulty->value, $enemy->fresh()->dungeon_difficulty);
        } finally {
            Enemy::query()->whereKey($enemy->id)->update(['dungeon_difficulty' => $enemy->dungeon_difficulty]);
            MappingChangeLog::query()->where('id', '>', $lastMappingChangeLogId)->delete();
        }
    }

    /**
     * @return array<string, array{DungeonDifficulty}>
     */
    public static function store_givenDungeonDifficulty_savesTheDungeonDifficulty_dataProvider(): array
    {
        return [
            '10-man' => [DungeonDifficulty::TEN_MAN],
            '25-man' => [DungeonDifficulty::TWENTY_FIVE_MAN],
            '20-man' => [DungeonDifficulty::TWENTY_MAN],
            '40-man' => [DungeonDifficulty::FORTY_MAN],
        ];
    }

    #[Test]
    public function store_givenUnknownDungeonDifficulty_returnsValidationErrorAndLeavesTheEnemyAlone(): void
    {
        // Arrange
        /** @var Enemy $enemy */
        $enemy = Enemy::query()->whereNotNull('floor_id')->orderBy('id')->firstOrFail();

        $lastMappingChangeLogId = (int)MappingChangeLog::query()->max('id');

        try {
            // Act
            $response = $this->putJson(
                sprintf('/ajax/admin/mappingVersion/%d/enemy/%d', $enemy->mapping_version_id, $enemy->id),
                $this->getStorePayload($enemy, max(DungeonDifficulty::values()) + 1),
            );

            // Assert
            $response->assertUnprocessable();
            $response->assertOnlyJsonValidationErrors(['dungeon_difficulty']);
            $this->assertSame($enemy->dungeon_difficulty, $enemy->fresh()->dungeon_difficulty);
        } finally {
            Enemy::query()->whereKey($enemy->id)->update(['dungeon_difficulty' => $enemy->dungeon_difficulty]);
            MappingChangeLog::query()->where('id', '>', $lastMappingChangeLogId)->delete();
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function getStorePayload(Enemy $enemy, int $dungeonDifficulty): array
    {
        return [
            'floor_id'           => $enemy->floor_id,
            'npc_id'             => $enemy->npc_id,
            'faction'            => $enemy->faction,
            'required'           => (int)$enemy->required,
            'skippable'          => (int)$enemy->skippable,
            'hyper_respawn'      => (int)$enemy->hyper_respawn,
            'kill_priority'      => $enemy->kill_priority ?? 0,
            'lat'                => $enemy->lat,
            'lng'                => $enemy->lng,
            'dungeon_difficulty' => $dungeonDifficulty,
        ];
    }
}
