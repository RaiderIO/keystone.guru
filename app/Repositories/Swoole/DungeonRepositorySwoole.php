<?php

namespace App\Repositories\Swoole;

use App\Models\Dungeon;
use App\Repositories\Database\DungeonRepository;
use App\Repositories\Swoole\Interfaces\DungeonRepositorySwooleInterface;
use Illuminate\Support\Collection;

class DungeonRepositorySwoole extends DungeonRepository implements DungeonRepositorySwooleInterface
{
    private Collection $dungeonsByChallengeModeId;

    public function __construct()
    {
        parent::__construct();

        $this->dungeonsByChallengeModeId = collect();
    }

    public function getByChallengeModeIdOrFail(int $challengeModeId): Dungeon
    {
        if ($this->dungeonsByChallengeModeId->isEmpty()) {
            $this->dungeonsByChallengeModeId = Dungeon::get()->keyBy('challenge_mode_id');

            foreach ($this->dungeonsByChallengeModeId as $dungeon) {
                $dungeon->load('currentMappingVersion');
            }
        }

        return clone $this->dungeonsByChallengeModeId->get($challengeModeId);
    }
}
