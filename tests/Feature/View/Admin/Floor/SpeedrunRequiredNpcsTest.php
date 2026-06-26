<?php

namespace Tests\Feature\View\Admin\Floor;

use App\Models\Dungeon;
use App\Models\Floor\Floor;
use App\Models\Speedrun\DungeonSpeedrunRequiredNpc;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('Mapping')]
final class SpeedrunRequiredNpcsTest extends PublicTestCase
{
    #[Test]
    public function speedrunRequiredNpcs_givenNoNpcs_rendersEmptyStateAndAddDropdownForEveryDifficulty(): void
    {
        // Arrange
        $floor = Floor::whereNotNull('dungeon_id')
            ->whereDoesntHave('dungeonSpeedrunRequiredNpcs')
            ->first();

        if ($floor === null) {
            $this->fail('No seeded floor without speedrun required NPCs found.');
        }

        // Act
        $rendered = $this->renderPartial($floor);

        // Assert
        $this->assertStringContainsString(
            __('view_admin.floor.edit.speedrun_required_npcs.no_npcs'),
            $rendered,
        );
        $this->assertStringNotContainsString('nav-tabs', $rendered);

        foreach (Dungeon::DIFFICULTY_ALL as $difficulty) {
            $this->assertStringContainsString(
                __('view_admin.floor.edit.speedrun_required_npcs.add_npc_for', [
                    'difficulty' => Dungeon::getDifficultyName($difficulty),
                ]),
                $rendered,
            );
        }
    }

    #[Test]
    public function speedrunRequiredNpcs_givenNpcsForSomeDifficulties_rendersTabsOnlyForThoseDifficulties(): void
    {
        // Arrange
        $floor = Floor::whereNotNull('dungeon_id')
            ->whereDoesntHave('dungeonSpeedrunRequiredNpcs')
            ->first();

        if ($floor === null) {
            $this->fail('No seeded floor without speedrun required NPCs found.');
        }

        $createdNpcs = collect([Dungeon::DIFFICULTY_ALL[Dungeon::DIFFICULTY_10_MAN], Dungeon::DIFFICULTY_ALL[Dungeon::DIFFICULTY_40_MAN]])
            ->map(static fn(int $difficulty): DungeonSpeedrunRequiredNpc => DungeonSpeedrunRequiredNpc::create([
                'floor_id'   => $floor->id,
                'difficulty' => $difficulty,
                'count'      => 1,
            ]));

        try {
            // Act
            $rendered = $this->renderPartial($floor->fresh());

            // Assert
            $this->assertStringContainsString('admin_speedrun_required_npcs_10_man_tab', $rendered);
            $this->assertStringContainsString('admin_speedrun_required_npcs_40_man_tab', $rendered);
            $this->assertStringNotContainsString('admin_speedrun_required_npcs_25_man_tab', $rendered);
            $this->assertStringNotContainsString('admin_speedrun_required_npcs_20_man_tab', $rendered);
            $this->assertStringNotContainsString(
                __('view_admin.floor.edit.speedrun_required_npcs.no_npcs'),
                $rendered,
            );
        } finally {
            DungeonSpeedrunRequiredNpc::whereIn('id', $createdNpcs->pluck('id'))->delete();
        }
    }

    private function renderPartial(Floor $floor): string
    {
        return view('admin.floor.speedrunrequirednpcs', [
            'dungeon' => $floor->dungeon,
            'floor'   => $floor,
        ])->render();
    }
}
