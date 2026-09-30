<?php

namespace Tests\Feature\Controller\Admin;

use App\Models\DungeonDifficulty;
use App\Models\Floor\Floor;
use App\Models\Npc\NpcDungeon;
use App\Models\Speedrun\DungeonSpeedrunRequiredNpc;
use App\Models\Speedrun\DungeonSpeedrunRequiredNpcNpc;
use App\Models\User;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('Controller')]
#[Group('Admin')]
final class DungeonSpeedrunRequiredNpcsControllerTest extends PublicTestCase
{
    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->be(User::findOrFail(1));
    }

    #[Test]
    public function create_givenValidDifficulty_returnsOk(): void
    {
        // Arrange
        $floor = Floor::whereNotNull('dungeon_id')->firstOrFail();

        // Act
        $response = $this->get(route('admin.dungeonspeedrunrequirednpc.new', [
            'dungeon'    => $floor->dungeon,
            'floor'      => $floor,
            'difficulty' => DungeonDifficulty::TEN_MAN->value,
        ]));

        // Assert
        $response->assertOk();
        $response->assertViewHas('floor', fn(Floor $viewFloor) => $viewFloor->id === $floor->id);
        $response->assertViewHas('difficulty', DungeonDifficulty::TEN_MAN->value);
    }

    #[Test]
    public function create_givenDifficultyOutsideDeclaredValues_returnsNotFound(): void
    {
        // Arrange
        $floor                = Floor::whereNotNull('dungeon_id')->firstOrFail();
        $undeclaredDifficulty = max(DungeonDifficulty::values()) + 1;

        // Act
        $response = $this->get(route('admin.dungeonspeedrunrequirednpc.new', [
            'dungeon'    => $floor->dungeon,
            'floor'      => $floor,
            'difficulty' => $undeclaredDifficulty,
        ]));

        // Assert
        $response->assertNotFound();
    }

    #[Test]
    public function createSave_givenNpcsOfTheDungeon_createsRequiredNpcWithLinkedNpcs(): void
    {
        // Arrange
        [$floor, $npcIds] = $this->findFloorWithTwoDungeonNpcs();
        $maxIdBefore      = (int)DungeonSpeedrunRequiredNpc::query()->max('id');
        $createdIds       = [];

        try {
            // Act
            $response = $this->post(route('admin.dungeonspeedrunrequirednpc.savenew', [
                'dungeon'    => $floor->dungeon,
                'floor'      => $floor,
                'difficulty' => DungeonDifficulty::TEN_MAN->value,
            ]), [
                'dungeon_id' => $floor->dungeon_id,
                'floor_id'   => $floor->id,
                'difficulty' => DungeonDifficulty::TEN_MAN->value,
                'npc_id'     => $npcIds[0],
                'npc2_id'    => $npcIds[1],
                'npc3_id'    => -1,
                'npc4_id'    => -1,
                'npc5_id'    => -1,
                'count'      => 3,
            ]);
            $createdIds = DungeonSpeedrunRequiredNpc::query()
                ->where('id', '>', $maxIdBefore)
                ->where('floor_id', $floor->id)
                ->where('difficulty', DungeonDifficulty::TEN_MAN->value)
                ->where('count', 3)
                ->pluck('id')
                ->all();

            // Assert
            $response->assertRedirect(route('admin.floor.edit', ['dungeon' => $floor->dungeon, 'floor' => $floor]));
            $this->assertCount(1, $createdIds);
            $this->assertEqualsCanonicalizing(
                $npcIds,
                DungeonSpeedrunRequiredNpcNpc::query()->where('dungeon_speedrun_required_npc_id', $createdIds[0])->pluck('npc_id')->all(),
            );
        } finally {
            DungeonSpeedrunRequiredNpcNpc::query()->whereIn('dungeon_speedrun_required_npc_id', $createdIds)->delete();
            DungeonSpeedrunRequiredNpc::query()->whereKey($createdIds)->delete();
        }
    }

    #[Test]
    public function createSave_givenNpcNotInTheDungeon_returnsValidationError(): void
    {
        // Arrange
        [$floor, $npcIds] = $this->findFloorWithTwoDungeonNpcs();
        $foreignNpcId     = NpcDungeon::query()->where('dungeon_id', '!=', $floor->dungeon_id)
            ->whereNotIn('npc_id', NpcDungeon::query()->where('dungeon_id', $floor->dungeon_id)->select('npc_id'))
            ->value('npc_id');
        $maxIdBefore = (int)DungeonSpeedrunRequiredNpc::query()->max('id');

        try {
            // Act
            $response = $this->post(route('admin.dungeonspeedrunrequirednpc.savenew', [
                'dungeon'    => $floor->dungeon,
                'floor'      => $floor,
                'difficulty' => DungeonDifficulty::TEN_MAN->value,
            ]), [
                'dungeon_id' => $floor->dungeon_id,
                'floor_id'   => $floor->id,
                'difficulty' => DungeonDifficulty::TEN_MAN->value,
                'npc_id'     => $foreignNpcId,
                'npc2_id'    => -1,
                'npc3_id'    => -1,
                'npc4_id'    => -1,
                'npc5_id'    => -1,
                'count'      => 3,
            ]);

            // Assert
            $this->assertNotNull($foreignNpcId);
            $response->assertSessionHasErrors('npc_id');
            $this->assertFalse(DungeonSpeedrunRequiredNpc::query()->where('id', '>', $maxIdBefore)->exists());
        } finally {
            $leakedIds = DungeonSpeedrunRequiredNpc::query()->where('id', '>', $maxIdBefore)->pluck('id')->all();
            DungeonSpeedrunRequiredNpcNpc::query()->whereIn('dungeon_speedrun_required_npc_id', $leakedIds)->delete();
            DungeonSpeedrunRequiredNpc::query()->whereKey($leakedIds)->delete();
        }
    }

    #[Test]
    public function delete_givenExistingRequiredNpc_deletesItAndRedirects(): void
    {
        // Arrange
        $floor       = Floor::whereNotNull('dungeon_id')->firstOrFail();
        $requiredNpc = DungeonSpeedrunRequiredNpc::create([
            'floor_id'   => $floor->id,
            'difficulty' => DungeonDifficulty::TEN_MAN->value,
            'count'      => 1,
        ]);

        try {
            // Act
            $response = $this->delete(route('admin.dungeonspeedrunrequirednpc.delete', [
                'dungeon'                    => $floor->dungeon,
                'floor'                      => $floor,
                'difficulty'                 => DungeonDifficulty::TEN_MAN->value,
                'dungeonspeedrunrequirednpc' => $requiredNpc,
            ]));

            // Assert
            $response->assertRedirect(route('admin.floor.edit', ['dungeon' => $floor->dungeon, 'floor' => $floor]));
            $this->assertFalse(DungeonSpeedrunRequiredNpc::query()->whereKey($requiredNpc->id)->exists());
        } finally {
            DungeonSpeedrunRequiredNpc::query()->whereKey($requiredNpc->id)->delete();
        }
    }

    /**
     * @return array{0: Floor, 1: array<int, int>}
     */
    private function findFloorWithTwoDungeonNpcs(): array
    {
        $dungeonId = NpcDungeon::query()
            ->whereIn('dungeon_id', Floor::query()->whereNotNull('dungeon_id')->select('dungeon_id'))
            ->groupBy('dungeon_id')
            ->havingRaw('count(distinct npc_id) >= 2')
            ->orderBy('dungeon_id')
            ->value('dungeon_id');

        $floor  = Floor::query()->where('dungeon_id', $dungeonId)->firstOrFail();
        $npcIds = NpcDungeon::query()->where('dungeon_id', $dungeonId)->distinct()->orderBy('npc_id')->limit(2)->pluck('npc_id')->all();

        return [$floor, $npcIds];
    }
}
