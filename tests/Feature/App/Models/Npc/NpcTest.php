<?php

namespace Tests\Feature\App\Models\Npc;

use App\Http\Controllers\Traits\ChangesMapping;
use App\Models\Dungeon;
use App\Models\Mapping\MappingChangeLog;
use App\Models\Npc\Npc;
use App\Models\Npc\NpcClassification;
use App\Models\Npc\NpcDungeon;
use App\Models\Npc\NpcType;
use App\Models\User;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('Npc')]
final class NpcTest extends PublicTestCase
{
    use ChangesMapping;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        // Npc/NpcDungeon use the SeederModel trait, which blocks delete() for non-admins.
        $this->be(User::findOrFail(1));
    }

    #[Test]
    public function getDungeonId_givenNpcBelongsToMultipleDungeons_returnsFirstDungeonId(): void
    {
        // Arrange - an NPC linked to more than one Dungeon row hydrates its `dungeons` relation as
        // a >1-row collection, which is the condition that arms Eloquent's lazy-loading prevention
        // on each of those Dungeon models (#4250).
        $dungeons = Dungeon::query()->limit(2)->get();
        $this->assertCount(2, $dungeons, 'Seeded DB must have at least 2 dungeons.');

        $npc = null;

        try {
            $npc = Npc::create([
                'id'                => 900000,
                'classification_id' => NpcClassification::ALL[NpcClassification::NPC_CLASSIFICATION_NORMAL],
                'npc_type_id'       => NpcType::HUMANOID,
                'name'              => 'Test NPC for #4250 regression coverage',
                'aggressiveness'    => 'aggressive',
            ]);

            foreach ($dungeons as $dungeon) {
                NpcDungeon::create([
                    'npc_id'     => $npc->id,
                    'dungeon_id' => $dungeon->id,
                ]);
            }

            // Act
            $dungeonId = $npc->getDungeonId();

            // Assert
            $this->assertSame($dungeons->first()->id, $dungeonId);
        } finally {
            NpcDungeon::query()->where('npc_id', 900000)->delete();
            $npc?->delete();
        }
    }

    #[Test]
    public function mappingChanged_givenNpcBelongsToMultipleDungeons_doesNotThrowLazyLoadingViolation(): void
    {
        // Arrange - reproduces the AdminToolsNpcController::npcimportsubmit() flow that threw
        // `Attempted to lazy load [floors] on model [App\Models\Dungeon]` (#4250): getDungeonId()
        // used to cache the `dungeons` relation on the Npc, which mappingChanged()'s toArray() call
        // would then serialize, lazy-loading each Dungeon's `floor_count` append in the process.
        $dungeons = Dungeon::query()->limit(2)->get();
        $this->assertCount(2, $dungeons, 'Seeded DB must have at least 2 dungeons.');

        $npc = null;

        try {
            $npc = Npc::create([
                'id'                => 900001,
                'classification_id' => NpcClassification::ALL[NpcClassification::NPC_CLASSIFICATION_NORMAL],
                'npc_type_id'       => NpcType::HUMANOID,
                'name'              => 'Test NPC for #4250 regression coverage',
                'aggressiveness'    => 'aggressive',
            ]);

            foreach ($dungeons as $dungeon) {
                NpcDungeon::create([
                    'npc_id'     => $npc->id,
                    'dungeon_id' => $dungeon->id,
                ]);
            }

            // Act & Assert - must not throw
            $this->mappingChanged($npc, $npc);
            $this->addToAssertionCount(1);
        } finally {
            MappingChangeLog::query()->where('model_id', 900001)->where('model_class', Npc::class)->delete();
            NpcDungeon::query()->where('npc_id', 900001)->delete();
            $npc?->delete();
        }
    }
}
