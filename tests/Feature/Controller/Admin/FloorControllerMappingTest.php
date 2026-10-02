<?php

namespace Tests\Feature\Controller\Admin;

use App\Models\Dungeon;
use App\Models\Floor\Floor;
use App\Models\Floor\FloorCoupling;
use App\Models\Mapping\MappingVersion;
use App\Models\User;
use App\Service\Dungeon\DungeonServiceInterface;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('Controller')]
#[Group('Admin')]
#[Group('Mapping')]
final class FloorControllerMappingTest extends PublicTestCase
{
    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->be(User::findOrFail(1));
    }

    #[Test]
    public function mapping_asAdmin_linksOtherDungeonsToTheirNewestMappingVersion(): void
    {
        // Arrange
        // The dungeon-context strip only shows dungeons for the user's current game version - pick
        // from that same set, or the expected link would never appear in the header regardless of fix.
        $contextDungeons = app(DungeonServiceInterface::class)->getDungeonsForGameVersion()
            ->filter(static fn(Dungeon $dungeon): bool => $dungeon->floors()->exists() && $dungeon->getCurrentMappingVersion() !== null)
            ->values();

        if ($contextDungeons->count() < 2) {
            $this->fail('Need at least 2 mapped dungeons in the current game version context to run this test.');
        }

        $dungeon = $contextDungeons->first();
        $floor   = $dungeon->floors()->firstOrFail();

        $otherDungeon                     = $contextDungeons->get(1);
        $otherDungeonNewestFloor          = $otherDungeon->floors()->firstOrFail();
        $otherDungeonNewestMappingVersion = $otherDungeon->getCurrentMappingVersion();

        // Act
        $response = $this->get(route('admin.floor.edit.mapping', [
            'dungeon'         => $dungeon,
            'floor'           => $floor,
            'mapping_version' => $dungeon->getCurrentMappingVersion(),
        ]));

        // Assert
        $response->assertOk();
        $response->assertSee(route('admin.floor.edit.mapping', [
            'dungeon'         => $otherDungeon,
            'floor'           => $otherDungeonNewestFloor,
            'mapping_version' => $otherDungeonNewestMappingVersion,
        ]), false);
    }

    #[Test]
    public function mapping_asAdmin_rendersMdtClonesControlsAsIconRailButtons(): void
    {
        // Arrange
        $dungeon   = Dungeon::query()->whereHas('floors')->whereHas('mappingVersions')->firstOrFail();
        $floor     = $dungeon->floors()->firstOrFail();
        $mdt       = preg_quote(e(__('view_common.maps.controls.elements.mdtclones.mdt')), '/');
        $autoSolve = preg_quote(e(__('view_common.maps.controls.elements.mdtclones.auto_solve')), '/');

        // Act
        $response = $this->get(route('admin.floor.edit.mapping', [
            'dungeon'         => $dungeon,
            'floor'           => $floor,
            'mapping_version' => $dungeon->getCurrentMappingVersion(),
        ]));

        // Assert
        $response->assertOk();
        $content = $response->getContent();
        $this->assertMatchesRegularExpression(
            sprintf('/<input type="checkbox" class="btn-check"[^>]*id="map_enemy_visuals_map_mdt_clones_to_enemies"[^>]*aria-label="%s"/', $mdt),
            $content,
        );
        $this->assertMatchesRegularExpression(
            sprintf('/<label class="btn btn-info" for="map_enemy_visuals_map_mdt_clones_to_enemies".*?<span class="map_controls_element_label_toggle" style="display: none;">\s*%s\s*<\/span>/s', $mdt),
            $content,
        );
        $this->assertMatchesRegularExpression(
            sprintf('/<button type="button" id="map_enemy_visuals_mdt_auto_solve"[^>]*aria-label="%s".*?<span class="map_controls_element_label_toggle" style="display: none;">\s*%s\s*<\/span>/s', $autoSolve, $autoSolve),
            $content,
        );
    }

    #[Test]
    public function mapping_givenMappingVersionOfAnotherDungeon_redirectsToDungeonEdit(): void
    {
        // Arrange
        $dungeon                    = Dungeon::query()->whereHas('floors')->whereHas('mappingVersions')->firstOrFail();
        $floor                      = $dungeon->floors()->firstOrFail();
        $otherDungeonMappingVersion = MappingVersion::query()->where('dungeon_id', '!=', $dungeon->id)->firstOrFail();

        // Act
        $response = $this->get(route('admin.floor.edit.mapping', [
            'dungeon'         => $dungeon,
            'floor'           => $floor,
            'mapping_version' => $otherDungeonMappingVersion,
        ]));

        // Assert
        $response->assertRedirect(route('admin.dungeon.edit', ['dungeon' => $dungeon]));
        $response->assertSessionHas('warning');
    }

    #[Test]
    public function mapping_givenUnknownMappingVersion_returnsNotFound(): void
    {
        // Arrange
        $dungeon = Dungeon::query()->whereHas('floors')->firstOrFail();
        $floor   = $dungeon->floors()->firstOrFail();

        // Act
        $response = $this->get(route('admin.floor.edit.mapping', [
            'dungeon'         => $dungeon,
            'floor'           => $floor,
            'mapping_version' => (int)MappingVersion::query()->max('id') + 1000,
        ]));

        // Assert
        $response->assertNotFound();
    }

    #[Test]
    public function update_givenFloorOfAnotherDungeon_redirectsWithoutChangingTheFloor(): void
    {
        // Arrange
        $dungeon      = Dungeon::query()->whereHas('floors')->firstOrFail();
        $otherDungeon = Dungeon::query()->whereKeyNot($dungeon->id)->firstOrFail();
        $floor        = null;
        $coupling     = null;

        try {
            $floor = Floor::create([
                'dungeon_id'   => $dungeon->id,
                'index'        => 99,
                'ui_map_id'    => 1,
                'name'         => 'Test floor',
                'ingame_min_x' => 0,
                'ingame_min_y' => 0,
                'ingame_max_x' => 1000,
                'ingame_max_y' => 1000,
            ]);
            $coupling = FloorCoupling::create([
                'floor1_id' => $floor->id,
                'floor2_id' => $dungeon->floors()->whereKeyNot($floor->id)->firstOrFail()->id,
                'direction' => FloorCoupling::DIRECTION_UP,
            ]);

            // Act
            $response = $this->patch(route('admin.floor.update', ['dungeon' => $otherDungeon, 'floor' => $floor]), [
                'name'      => 'Renamed through the wrong dungeon',
                'index'     => 98,
                'ui_map_id' => 2,
            ]);

            // Assert
            $response->assertRedirect(route('admin.dungeon.edit', ['dungeon' => $otherDungeon]));
            $response->assertSessionHas('warning');
            $floor->refresh();
            $this->assertSame('Test floor', $floor->name);
            $this->assertSame(99, $floor->index);
            $this->assertSame(1, $floor->ui_map_id);
            $this->assertTrue(FloorCoupling::query()->whereKey($coupling->id)->exists());
        } finally {
            $coupling?->delete();
            $floor?->delete();
        }
    }
}
