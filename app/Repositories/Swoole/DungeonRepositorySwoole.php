<?php

namespace App\Repositories\Swoole;

use App\Models\Dungeon;
use App\Models\Mapping\MappingVersion;
use App\Repositories\Database\DungeonRepository;
use App\Repositories\Swoole\Interfaces\DungeonRepositorySwooleInterface;
use Illuminate\Support\Collection;

class DungeonRepositorySwoole extends DungeonRepository implements DungeonRepositorySwooleInterface
{
    /** @var Collection<Dungeon> */
    private Collection $dungeonsByChallengeModeId;

    private readonly Collection $dungeonMappingVersions;

    public function __construct()
    {
        parent::__construct();

        $this->dungeonsByChallengeModeId = collect();
        $this->dungeonMappingVersions    = collect();
    }

    #[\Override]
    public function getByChallengeModeIdOrFail(int $challengeModeId): Dungeon
    {
        if ($this->dungeonsByChallengeModeId->isEmpty()) {
            /** @var Collection<Dungeon> $dungeonsByChallengeModeId */
            $this->dungeonsByChallengeModeId = Dungeon::get()->keyBy('challenge_mode_id');

            foreach ($this->dungeonsByChallengeModeId as $dungeon) {
                // Build the cache
                $dungeon->getCurrentMappingVersion();
            }
        }

        /** @var Dungeon $dungeon */
        $dungeon = clone $this->dungeonsByChallengeModeId->get($challengeModeId);

        return $dungeon;
    }

    #[\Override]
    public function getMappingVersionByVersion(Dungeon $dungeon, int $version): ?MappingVersion
    {
        if (!$this->dungeonMappingVersions->has($dungeon->id)) {
            $this->dungeonMappingVersions->put($dungeon->id, $dungeon->mappingVersions()->get());
        }

        /** @var Collection<MappingVersion> $mappingVersions */
        $mappingVersions = $this->dungeonMappingVersions->get($dungeon->id);
        /** @var MappingVersion $mappingVersion */
        $mappingVersion = $mappingVersions->firstWhere('version', $version);

        return $mappingVersion;
    }
}
