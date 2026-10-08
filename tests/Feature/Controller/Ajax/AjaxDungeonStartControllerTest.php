<?php

namespace Tests\Feature\Controller\Ajax;

use App\Events\Models\DungeonStart\DungeonStartChangedEvent;
use App\Models\Dungeon;
use App\Models\DungeonStart;
use App\Models\Mapping\MappingVersion;
use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\AjaxPublicTestCase;

#[Group('DungeonStart')]
#[Group('AjaxDungeonStartController')]
final class AjaxDungeonStartControllerTest extends AjaxPublicTestCase
{
    #[Test]
    public function store_givenValidDungeonStart_createsIt(): void
    {
        // Arrange
        $mappingVersion = $this->getNonFacadeMappingVersion();
        $floorId        = $mappingVersion->dungeon->floors->first()->id;
        $dungeonStartId = null;

        try {
            // Act
            $response = $this->post(route('ajax.admin.dungeonstart.create', ['mappingVersion' => $mappingVersion]), [
                'mapping_version_id' => $mappingVersion->id,
                'floor_id'           => $floorId,
                'lat'                => -100.5,
                'lng'                => 150.5,
                'comment'            => 'mapping.start.east',
            ]);

            // Assert
            $response->assertCreated();
            $dungeonStartId = json_decode($response->content(), true)['id'];

            /** @var DungeonStart $dungeonStart */
            $dungeonStart = DungeonStart::query()->findOrFail($dungeonStartId);
            $this->assertSame($mappingVersion->id, $dungeonStart->mapping_version_id);
            $this->assertSame($floorId, $dungeonStart->floor_id);
            $this->assertEqualsWithDelta(-100.5, $dungeonStart->lat, 0.001);
            $this->assertEqualsWithDelta(150.5, $dungeonStart->lng, 0.001);
            $this->assertSame('mapping.start.east', $dungeonStart->comment);
        } finally {
            if ($dungeonStartId !== null) {
                DungeonStart::query()->whereKey($dungeonStartId)->delete();
            }
        }
    }

    #[Test]
    public function store_givenExistingDungeonStart_updatesIt(): void
    {
        // Arrange
        $mappingVersion = $this->getNonFacadeMappingVersion();
        $floorId        = $mappingVersion->dungeon->floors->first()->id;
        $dungeonStart   = DungeonStart::factory()->create([
            'mapping_version_id' => $mappingVersion->id,
            'floor_id'           => $floorId,
            'comment'            => 'mapping.start.east',
        ]);

        try {
            // Act
            $response = $this->put(route('ajax.admin.dungeonstart.update', ['mappingVersion' => $mappingVersion, 'dungeonStart' => $dungeonStart]), [
                'mapping_version_id' => $mappingVersion->id,
                'floor_id'           => $floorId,
                'lat'                => -110.0,
                'lng'                => 160.0,
                'comment'            => 'mapping.start.west',
            ]);

            // Assert
            $response->assertOk();
            $dungeonStart->refresh();
            $this->assertEqualsWithDelta(-110.0, $dungeonStart->lat, 0.001);
            $this->assertSame('mapping.start.west', $dungeonStart->comment);
        } finally {
            $dungeonStart->delete();
        }
    }

    #[Test]
    public function store_givenUnknownFloor_returnsValidationError(): void
    {
        // Arrange
        $mappingVersion = $this->getNonFacadeMappingVersion();
        $countBefore    = DungeonStart::query()->count();

        // Act
        $response = $this->postJson(route('ajax.admin.dungeonstart.create', ['mappingVersion' => $mappingVersion]), [
            'mapping_version_id' => $mappingVersion->id,
            'floor_id'           => -1,
            'lat'                => -100.5,
            'lng'                => 150.5,
        ]);

        // Assert
        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('floor_id');
        $this->assertSame($countBefore, DungeonStart::query()->count());
    }

    #[Test]
    public function store_givenTargetDungeon_savesIt(): void
    {
        // Arrange
        $mappingVersion = $this->getNonFacadeMappingVersion();
        $floorId        = $mappingVersion->dungeon->floors->first()->id;
        /** @var Dungeon $targetDungeon */
        $targetDungeon  = Dungeon::query()->whereKeyNot($mappingVersion->dungeon_id)->firstOrFail();
        $dungeonStartId = null;

        try {
            // Act
            $response = $this->post(route('ajax.admin.dungeonstart.create', ['mappingVersion' => $mappingVersion]), [
                'mapping_version_id' => $mappingVersion->id,
                'floor_id'           => $floorId,
                'target_dungeon_id'  => $targetDungeon->id,
                'lat'                => -100.5,
                'lng'                => 150.5,
            ]);

            // Assert
            $response->assertCreated();
            $dungeonStartId = json_decode($response->content(), true)['id'];
            $this->assertSame($targetDungeon->id, DungeonStart::query()->findOrFail($dungeonStartId)->target_dungeon_id);
        } finally {
            if ($dungeonStartId !== null) {
                DungeonStart::query()->whereKey($dungeonStartId)->delete();
            }
        }
    }

    #[Test]
    public function store_givenNoTargetDungeonSelected_clearsTheTargetDungeon(): void
    {
        // Arrange
        $mappingVersion = $this->getNonFacadeMappingVersion();
        $floorId        = $mappingVersion->dungeon->floors->first()->id;
        /** @var Dungeon $targetDungeon */
        $targetDungeon = Dungeon::query()->whereKeyNot($mappingVersion->dungeon_id)->firstOrFail();
        $dungeonStart  = DungeonStart::factory()->create([
            'mapping_version_id' => $mappingVersion->id,
            'floor_id'           => $floorId,
            'target_dungeon_id'  => $targetDungeon->id,
        ]);

        try {
            // Act
            $response = $this->put(route('ajax.admin.dungeonstart.update', ['mappingVersion' => $mappingVersion, 'dungeonStart' => $dungeonStart]), [
                'mapping_version_id' => $mappingVersion->id,
                'floor_id'           => $floorId,
                'target_dungeon_id'  => -1,
                'lat'                => -110.0,
                'lng'                => 160.0,
            ]);

            // Assert
            $response->assertOk();
            $this->assertNull($dungeonStart->refresh()->target_dungeon_id);
        } finally {
            $dungeonStart->delete();
        }
    }

    #[Test]
    public function store_givenUnknownTargetDungeon_returnsValidationError(): void
    {
        // Arrange
        $mappingVersion = $this->getNonFacadeMappingVersion();
        $countBefore    = DungeonStart::query()->count();

        // Act
        $response = $this->postJson(route('ajax.admin.dungeonstart.create', ['mappingVersion' => $mappingVersion]), [
            'mapping_version_id' => $mappingVersion->id,
            'floor_id'           => $mappingVersion->dungeon->floors->first()->id,
            'target_dungeon_id'  => (int)Dungeon::query()->max('id') + 1,
            'lat'                => -100.5,
            'lng'                => 150.5,
        ]);

        // Assert
        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('target_dungeon_id');
        $this->assertSame($countBefore, DungeonStart::query()->count());
    }

    #[Test]
    #[DataProvider('store_givenTargetDungeon_returnsWhetherItLeadsIntoARaid_dataProvider')]
    public function store_givenTargetDungeon_returnsWhetherItLeadsIntoARaid(bool $targetIsRaid): void
    {
        // Arrange
        $mappingVersion = $this->getNonFacadeMappingVersion();
        /** @var Dungeon $targetDungeon */
        $targetDungeon  = Dungeon::query()->whereKeyNot($mappingVersion->dungeon_id)->where('raid', $targetIsRaid)->firstOrFail();
        $dungeonStartId = null;

        try {
            // Act
            $response = $this->post(route('ajax.admin.dungeonstart.create', ['mappingVersion' => $mappingVersion]), [
                'mapping_version_id' => $mappingVersion->id,
                'floor_id'           => $mappingVersion->dungeon->floors->first()->id,
                'target_dungeon_id'  => $targetDungeon->id,
                'lat'                => -100.5,
                'lng'                => 150.5,
            ]);

            // Assert
            $response->assertCreated();
            $dungeonStartId = $response->json('id');
            $response->assertJsonPath('raid', $targetIsRaid);
        } finally {
            if ($dungeonStartId !== null) {
                DungeonStart::query()->whereKey($dungeonStartId)->delete();
            }
        }
    }

    /**
     * @return array<string, array{bool}>
     */
    public static function store_givenTargetDungeon_returnsWhetherItLeadsIntoARaid_dataProvider(): array
    {
        return [
            'target is a raid'    => [true],
            'target is a dungeon' => [false],
        ];
    }

    #[Test]
    public function store_givenTargetRaid_broadcastsTheRaidFlagAfterQueueSerialization(): void
    {
        // Arrange
        Event::fake([DungeonStartChangedEvent::class]);
        $mappingVersion = $this->getNonFacadeMappingVersion();
        /** @var Dungeon $targetRaid */
        $targetRaid     = Dungeon::query()->whereKeyNot($mappingVersion->dungeon_id)->where('raid', true)->firstOrFail();
        $dungeonStartId = null;

        try {
            // Act
            $response = $this->post(route('ajax.admin.dungeonstart.create', ['mappingVersion' => $mappingVersion]), [
                'mapping_version_id' => $mappingVersion->id,
                'floor_id'           => $mappingVersion->dungeon->floors->first()->id,
                'target_dungeon_id'  => $targetRaid->id,
                'lat'                => -100.5,
                'lng'                => 150.5,
            ]);

            // Assert
            $response->assertCreated();
            $dungeonStartId = $response->json('id');
            Event::assertDispatched(DungeonStartChangedEvent::class, function (DungeonStartChangedEvent $event): bool {
                // The broadcast is queued, so the worker sees a model re-fetched from the database
                /** @var DungeonStartChangedEvent $queuedEvent */
                $queuedEvent = unserialize(serialize($event));
                $model       = $queuedEvent->broadcastWith()['model']->toArray();

                $this->assertArrayHasKey('raid', $model);
                $this->assertTrue($model['raid']);

                return true;
            });
        } finally {
            if ($dungeonStartId !== null) {
                DungeonStart::query()->whereKey($dungeonStartId)->delete();
            }
        }
    }

    #[Test]
    public function delete_givenDungeonStart_deletesIt(): void
    {
        // Arrange
        $mappingVersion = $this->getNonFacadeMappingVersion();
        $dungeonStart   = DungeonStart::factory()->create([
            'mapping_version_id' => $mappingVersion->id,
            'floor_id'           => $mappingVersion->dungeon->floors->first()->id,
        ]);

        try {
            // Act
            $response = $this->delete(route('ajax.admin.dungeonstart.delete', ['mappingVersion' => $mappingVersion, 'dungeonStart' => $dungeonStart]));

            // Assert
            $response->assertNoContent();
            $this->assertNull(DungeonStart::query()->find($dungeonStart->id));
        } finally {
            DungeonStart::query()->whereKey($dungeonStart->id)->delete();
        }
    }

    private function getNonFacadeMappingVersion(): MappingVersion
    {
        /** @var MappingVersion|null $mappingVersion */
        $mappingVersion = MappingVersion::query()
            ->where('facade_enabled', false)
            ->whereHas('dungeon', static fn($query) => $query->where('active', true))
            ->with('dungeon.floors')
            ->orderByDesc('id')
            ->first();

        if ($mappingVersion === null || !$mappingVersion->dungeon instanceof Dungeon) {
            $this->fail('No non-facade mapping version of an active dungeon found for testing.');
        }

        return $mappingVersion;
    }
}
