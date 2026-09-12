<?php

namespace Tests\Fixtures\Traits;

use App\Models\GameVersion\GameVersion;
use App\Models\Npc\Npc;
use App\Models\Npc\NpcClass;
use App\Models\Npc\NpcClassification;
use App\Models\Npc\NpcType;
use Tests\TestCase;

/**
 * @mixin TestCase
 */
trait CreatesNpc
{
    /** @var array<int, Npc> */
    private array $createdNpcs = [];

    /**
     * An unsaved NPC, for code that only reads attributes.
     *
     * @param array<string, mixed>|null $attributes
     */
    public function createNpc(?array $attributes = null): Npc
    {
        return new Npc($attributes ?? $this->getNpcDefaultAttributes());
    }

    /**
     * @return array<string, int>
     */
    public function getNpcDefaultAttributes(): array
    {
        return [
            'id' => 123123,
        ];
    }

    /**
     * A persisted NPC of the test's own, with no dungeons, enemies, spells, characteristics or observations - the
     * state a test asserting an empty result needs, which no seeded NPC promises to be in. Deleted again when the
     * test's application is torn down; Npc::deleting removes the NPC-owned rows a test may have added.
     *
     * @param array<string, mixed> $attributes Any `npcs` column.
     */
    protected function createNpcInDatabase(array $attributes = []): Npc
    {
        if ($this->createdNpcs === []) {
            $this->beforeApplicationDestroyed(fn() => $this->deleteCreatedNpcs());
        }

        $npc = Npc::query()->create(array_merge([
            'id'                => max(900_000_000, (int)Npc::query()->max('id') + 1),
            'game_version_id'   => GameVersion::getDefaultGameVersion()->id,
            'classification_id' => NpcClassification::ALL[NpcClassification::NPC_CLASSIFICATION_NORMAL],
            'npc_type_id'       => NpcType::HUMANOID,
            'npc_class_id'      => NpcClass::ALL[NpcClass::NPC_CLASS_MELEE],
            'name'              => 'Test Npc',
            'aggressiveness'    => Npc::AGGRESSIVENESS_AGGRESSIVE,
            'dangerous'         => false,
            'truesight'         => false,
            'runs_away_in_fear' => false,
        ], $attributes));

        $this->createdNpcs[] = $npc;

        return $npc;
    }

    /**
     * Runs on teardown by itself; call it directly only when the NPC must be gone before the test ends.
     */
    protected function deleteCreatedNpcs(): void
    {
        foreach ($this->createdNpcs as $npc) {
            $npc->delete();
        }

        $this->createdNpcs = [];
    }
}
