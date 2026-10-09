<?php

namespace Tests\Feature\Controller\Ajax;

use App\Models\Dungeon;
use App\Models\DungeonTransport;
use App\Models\MapIconType;
use App\Models\Mapping\MappingVersion;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\AjaxPublicTestCase;

#[Group('DungeonTransport')]
#[Group('AjaxDungeonTransportController')]
final class AjaxDungeonTransportControllerTest extends AjaxPublicTestCase
{
    #[Test]
    public function store_givenValidDungeonTransport_createsIt(): void
    {
        // Arrange
        $mappingVersion     = $this->getNonFacadeMappingVersion();
        $floorId            = $mappingVersion->dungeon->floors->first()->id;
        $partner            = $this->createTransport($mappingVersion);
        $targetDungeon      = Dungeon::query()->whereKeyNot($mappingVersion->dungeon_id)->firstOrFail();
        $dungeonTransportId = null;

        try {
            // Act
            $response = $this->post(route('ajax.admin.dungeontransport.create', ['mappingVersion' => $mappingVersion]), [
                'mapping_version_id'          => $mappingVersion->id,
                'floor_id'                    => $floorId,
                'map_icon_type_id'            => MapIconType::ALL[MapIconType::MAP_ICON_TYPE_PORTAL_GREEN],
                'linked_dungeon_transport_id' => $partner->id,
                'target_dungeon_id'           => $targetDungeon->id,
                'link_key'                    => 'boat-ratchet-booty-bay',
                'lat'                         => -100.5,
                'lng'                         => 150.5,
                'comment'                     => 'To the docks',
            ]);

            // Assert
            $response->assertCreated();
            $dungeonTransportId = $response->json('id');

            /** @var DungeonTransport $dungeonTransport */
            $dungeonTransport = DungeonTransport::query()->findOrFail($dungeonTransportId);
            $this->assertSame($mappingVersion->id, $dungeonTransport->mapping_version_id);
            $this->assertSame($floorId, $dungeonTransport->floor_id);
            $this->assertSame(MapIconType::ALL[MapIconType::MAP_ICON_TYPE_PORTAL_GREEN], $dungeonTransport->map_icon_type_id);
            $this->assertSame($partner->id, $dungeonTransport->linked_dungeon_transport_id);
            $this->assertSame($targetDungeon->id, $dungeonTransport->target_dungeon_id);
            $this->assertSame('boat-ratchet-booty-bay', $dungeonTransport->link_key);
            $this->assertEqualsWithDelta(-100.5, $dungeonTransport->lat, 0.001);
            $this->assertEqualsWithDelta(150.5, $dungeonTransport->lng, 0.001);
            $this->assertSame('To the docks', $dungeonTransport->comment);
        } finally {
            if ($dungeonTransportId !== null) {
                DungeonTransport::query()->whereKey($dungeonTransportId)->delete();
            }

            $partner->delete();
        }
    }

    #[Test]
    public function store_givenNoneSelected_clearsTheLinkTargetDungeonAndLinkKey(): void
    {
        // Arrange
        $mappingVersion   = $this->getNonFacadeMappingVersion();
        $partner          = $this->createTransport($mappingVersion);
        $dungeonTransport = $this->createTransport($mappingVersion, [
            'linked_dungeon_transport_id' => $partner->id,
            'target_dungeon_id'           => Dungeon::query()->whereKeyNot($mappingVersion->dungeon_id)->value('id'),
            'link_key'                    => 'boat-ratchet-booty-bay',
        ]);

        try {
            // Act
            $response = $this->put($this->updateRoute($mappingVersion, $dungeonTransport), [
                'mapping_version_id'          => $mappingVersion->id,
                'floor_id'                    => $dungeonTransport->floor_id,
                'map_icon_type_id'            => $dungeonTransport->map_icon_type_id,
                'linked_dungeon_transport_id' => -1,
                'target_dungeon_id'           => -1,
                'link_key'                    => '',
                'lat'                         => -110.0,
                'lng'                         => 160.0,
            ]);

            // Assert
            $response->assertOk();
            $dungeonTransport->refresh();
            $this->assertNull($dungeonTransport->linked_dungeon_transport_id);
            $this->assertNull($dungeonTransport->target_dungeon_id);
            $this->assertNull($dungeonTransport->link_key);
            $this->assertEqualsWithDelta(-110.0, $dungeonTransport->lat, 0.001);
        } finally {
            $dungeonTransport->delete();
            $partner->delete();
        }
    }

    #[Test]
    public function store_givenUnknownMapIconType_returnsValidationError(): void
    {
        // Arrange
        $mappingVersion = $this->getNonFacadeMappingVersion();
        $countBefore    = DungeonTransport::query()->count();

        // Act
        $response = $this->postJson(route('ajax.admin.dungeontransport.create', ['mappingVersion' => $mappingVersion]), [
            ...$this->validAttributes($mappingVersion),
            'map_icon_type_id' => (int)MapIconType::query()->max('id') + 1,
        ]);

        // Assert
        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('map_icon_type_id');
        $this->assertSame($countBefore, DungeonTransport::query()->count());
    }

    #[Test]
    public function store_givenLinkedTransportOfAnotherMappingVersion_returnsValidationError(): void
    {
        // Arrange
        $mappingVersion      = $this->getNonFacadeMappingVersion();
        $otherMappingVersion = MappingVersion::query()->whereKeyNot($mappingVersion->id)->with('dungeon.floors')->firstOrFail();
        $foreignTransport    = $this->createTransport($otherMappingVersion);
        $countBefore         = DungeonTransport::query()->count();

        try {
            // Act
            $response = $this->postJson(route('ajax.admin.dungeontransport.create', ['mappingVersion' => $mappingVersion]), [
                ...$this->validAttributes($mappingVersion),
                'linked_dungeon_transport_id' => $foreignTransport->id,
            ]);

            // Assert
            $response->assertUnprocessable();
            $response->assertJsonValidationErrors('linked_dungeon_transport_id');
            $this->assertSame($countBefore, DungeonTransport::query()->count());
        } finally {
            $foreignTransport->delete();
        }
    }

    #[Test]
    public function store_givenBodyMappingVersionOfTheLinkedTransportButAnotherRouteMappingVersion_returnsValidationError(): void
    {
        // Arrange - the controller stores into the route's mapping version whatever the body says
        $mappingVersion      = $this->getNonFacadeMappingVersion();
        $otherMappingVersion = MappingVersion::query()->whereKeyNot($mappingVersion->id)->with('dungeon.floors')->firstOrFail();
        $foreignTransport    = $this->createTransport($otherMappingVersion);
        $countBefore         = DungeonTransport::query()->count();

        try {
            // Act
            $response = $this->postJson(route('ajax.admin.dungeontransport.create', ['mappingVersion' => $mappingVersion]), [
                ...$this->validAttributes($mappingVersion),
                'mapping_version_id'          => $otherMappingVersion->id,
                'linked_dungeon_transport_id' => $foreignTransport->id,
            ]);

            // Assert
            $response->assertUnprocessable();
            $response->assertJsonValidationErrors('linked_dungeon_transport_id');
            $this->assertSame($countBefore, DungeonTransport::query()->count());
        } finally {
            $foreignTransport->delete();
        }
    }

    #[Test]
    public function store_givenLinkToItself_returnsValidationError(): void
    {
        // Arrange
        $mappingVersion   = $this->getNonFacadeMappingVersion();
        $dungeonTransport = $this->createTransport($mappingVersion);

        try {
            // Act
            $response = $this->putJson($this->updateRoute($mappingVersion, $dungeonTransport), [
                ...$this->validAttributes($mappingVersion),
                'id'                          => $dungeonTransport->id,
                'linked_dungeon_transport_id' => $dungeonTransport->id,
            ]);

            // Assert
            $response->assertUnprocessable();
            $response->assertJsonValidationErrors('linked_dungeon_transport_id');
            $this->assertNull($dungeonTransport->refresh()->linked_dungeon_transport_id);
        } finally {
            $dungeonTransport->delete();
        }
    }

    #[Test]
    public function store_givenUnknownTargetDungeon_returnsValidationError(): void
    {
        // Arrange
        $mappingVersion = $this->getNonFacadeMappingVersion();
        $countBefore    = DungeonTransport::query()->count();

        // Act
        $response = $this->postJson(route('ajax.admin.dungeontransport.create', ['mappingVersion' => $mappingVersion]), [
            ...$this->validAttributes($mappingVersion),
            'target_dungeon_id' => (int)Dungeon::query()->max('id') + 1,
        ]);

        // Assert
        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('target_dungeon_id');
        $this->assertSame($countBefore, DungeonTransport::query()->count());
    }

    #[Test]
    public function delete_givenLinkedTransport_deletesItAndUnlinksItsPartners(): void
    {
        // Arrange
        $mappingVersion   = $this->getNonFacadeMappingVersion();
        $dungeonTransport = $this->createTransport($mappingVersion);
        $partner          = $this->createTransport($mappingVersion, ['linked_dungeon_transport_id' => $dungeonTransport->id]);
        $bystanderTarget  = $this->createTransport($mappingVersion);
        $bystander        = $this->createTransport($mappingVersion, ['linked_dungeon_transport_id' => $bystanderTarget->id]);

        try {
            // Act
            $response = $this->delete(route('ajax.admin.dungeontransport.delete', [
                'mappingVersion'   => $mappingVersion,
                'dungeonTransport' => $dungeonTransport,
            ]));

            // Assert
            $response->assertNoContent();
            $this->assertNull(DungeonTransport::query()->find($dungeonTransport->id));
            $this->assertNull($partner->refresh()->linked_dungeon_transport_id);
            $this->assertSame($bystanderTarget->id, $bystander->refresh()->linked_dungeon_transport_id);
        } finally {
            DungeonTransport::query()
                ->whereKey([$dungeonTransport->id, $partner->id, $bystanderTarget->id, $bystander->id])
                ->delete();
        }
    }

    /**
     * @param array<string, mixed> $attributes
     */
    private function createTransport(MappingVersion $mappingVersion, array $attributes = []): DungeonTransport
    {
        return DungeonTransport::factory()->create([
            'mapping_version_id' => $mappingVersion->id,
            'floor_id'           => $mappingVersion->dungeon->floors->first()->id,
            ...$attributes,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validAttributes(MappingVersion $mappingVersion): array
    {
        return [
            'mapping_version_id' => $mappingVersion->id,
            'floor_id'           => $mappingVersion->dungeon->floors->first()->id,
            'map_icon_type_id'   => MapIconType::ALL[MapIconType::MAP_ICON_TYPE_PORTAL_BLUE],
            'lat'                => -100.5,
            'lng'                => 150.5,
        ];
    }

    private function updateRoute(MappingVersion $mappingVersion, DungeonTransport $dungeonTransport): string
    {
        return route('ajax.admin.dungeontransport.update', [
            'mappingVersion'   => $mappingVersion,
            'dungeonTransport' => $dungeonTransport,
        ]);
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
