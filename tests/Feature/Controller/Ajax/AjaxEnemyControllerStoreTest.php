<?php

namespace Tests\Feature\Controller\Ajax;

use App\Models\Enemy;
use App\Models\Mapping\MappingVersion;
use Illuminate\Support\Facades\DB;
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

        // Act
        $response = $this->put(sprintf('/ajax/admin/mappingVersion/%d/enemy/%d', $mappingVersion->id, $enemy->id), $payload);

        // Assert
        $response->assertSuccessful();
        $this->assertEmpty(
            array_filter($queries, static fn(string $sql) => str_contains($sql, 'enemy_active_auras')),
            'Saving an enemy must not query enemy_active_auras',
        );
    }
}
