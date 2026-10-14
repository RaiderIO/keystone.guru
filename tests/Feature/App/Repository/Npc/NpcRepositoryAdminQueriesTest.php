<?php

namespace Tests\Feature\App\Repository\Npc;

use App\Models\Dungeon;
use App\Models\Npc\NpcDungeon;
use App\Models\Npc\NpcSpell;
use App\Models\Translation\Translation;
use App\Repositories\Database\Npc\NpcRepository;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fixtures\Traits\CreatesNpc;
use Tests\Fixtures\Traits\CreatesSpell;
use Tests\TestCases\PublicTestCase;

#[Group('NpcRepository')]
final class NpcRepositoryAdminQueriesTest extends PublicTestCase
{
    use CreatesNpc;
    use CreatesSpell;

    private NpcRepository $repository;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = new NpcRepository();
    }

    #[Test]
    public function paginateWithSpells_givenDungeon_returnsOnlyNpcsWithSpellsInThatDungeon(): void
    {
        // Arrange
        [$dungeon, $otherDungeon] = Dungeon::query()->orderBy('id')->limit(2)->get()->all();
        $spell                    = $this->createSpell();
        $inDungeonWithSpell       = $this->createNpcInDatabase();
        $inDungeonWithoutSpell    = $this->createNpcInDatabase();
        $otherDungeonWithSpell    = $this->createNpcInDatabase();
        NpcDungeon::create(['npc_id' => $inDungeonWithSpell->id, 'dungeon_id' => $dungeon->id]);
        NpcDungeon::create(['npc_id' => $inDungeonWithoutSpell->id, 'dungeon_id' => $dungeon->id]);
        NpcDungeon::create(['npc_id' => $otherDungeonWithSpell->id, 'dungeon_id' => $otherDungeon->id]);
        NpcSpell::create(['npc_id' => $inDungeonWithSpell->id, 'spell_id' => $spell->id]);
        NpcSpell::create(['npc_id' => $otherDungeonWithSpell->id, 'spell_id' => $spell->id]);

        // Act
        $ids = collect($this->repository->paginateWithSpells($dungeon, 1000)->items())->pluck('id');

        // Assert
        $this->assertTrue($ids->contains($inDungeonWithSpell->id));
        $this->assertFalse($ids->contains($inDungeonWithoutSpell->id));
        $this->assertFalse($ids->contains($otherDungeonWithSpell->id));
    }

    #[Test]
    public function paginateWithSpells_givenNoDungeon_returnsNpcsWithSpellsOfEveryDungeon(): void
    {
        // Arrange
        $spell     = $this->createSpell();
        $withSpell = $this->createNpcInDatabase();
        $without   = $this->createNpcInDatabase();
        NpcSpell::create(['npc_id' => $withSpell->id, 'spell_id' => $spell->id]);

        // Act
        $ids = collect($this->repository->paginateWithSpells(null, 1000)->items())->pluck('id');

        // Assert
        $this->assertTrue($ids->contains($withSpell->id));
        $this->assertFalse($ids->contains($without->id));
    }

    #[Test]
    public function getWithoutDisplayId_givenNpcsWithAndWithoutDisplayId_returnsOnlyTheOnesWithout(): void
    {
        // Arrange
        $without = $this->createNpcInDatabase(['display_id' => null]);
        $with    = $this->createNpcInDatabase(['display_id' => 1]);

        // Act
        $ids = $this->repository->getWithoutDisplayId()->pluck('id');

        // Assert
        $this->assertTrue($ids->contains($without->id));
        $this->assertFalse($ids->contains($with->id));
    }

    #[Test]
    public function getForBolsteringWhitelistDropdown_givenNpcsInTwoDungeons_ordersByDungeonIdThenName(): void
    {
        // Arrange
        [$firstDungeon, $secondDungeon] = Dungeon::query()->orderBy('id')->limit(2)->get()->all();
        $translationIds                 = [];

        try {
            $laterName    = $this->createNpcInDatabase(['name' => 'zz_order_test_later']);
            $earlierName  = $this->createNpcInDatabase(['name' => 'zz_order_test_earlier']);
            $otherDungeon = $this->createNpcInDatabase(['name' => 'zz_order_test_aaa']);
            foreach ([$laterName, $earlierName, $otherDungeon] as $npc) {
                $translationIds[] = Translation::query()->create(['locale' => 'en_US', 'key' => $npc->name, 'translation' => $npc->name])->id;
            }
            NpcDungeon::create(['npc_id' => $laterName->id, 'dungeon_id' => $firstDungeon->id]);
            NpcDungeon::create(['npc_id' => $earlierName->id, 'dungeon_id' => $firstDungeon->id]);
            NpcDungeon::create(['npc_id' => $otherDungeon->id, 'dungeon_id' => $secondDungeon->id]);

            // Act
            $ids = $this->repository->getForBolsteringWhitelistDropdown()
                ->whereIn('id', [$laterName->id, $earlierName->id, $otherDungeon->id])
                ->pluck('id')
                ->values()
                ->all();

            // Assert
            $this->assertSame([$earlierName->id, $laterName->id, $otherDungeon->id], $ids);
        } finally {
            Translation::query()->whereIn('id', $translationIds)->delete();
        }
    }
}
