<?php

namespace App\Repositories\Swoole;

use App\Models\Mapping\MappingVersion;
use App\Repositories\Database\Npc\NpcRepository;
use App\Repositories\Swoole\Interfaces\NpcRepositorySwooleInterface;
use App\Repositories\Swoole\Traits\ClonesCollections;
use Illuminate\Support\Collection;

class NpcRepositorySwoole extends NpcRepository implements NpcRepositorySwooleInterface
{
    use ClonesCollections;

    private Collection $inUseNpcsByMappingVersionId;
    private Collection $inUseNpcIdsByMappingVersionId;

    public function __construct()
    {
        parent::__construct();

        $this->inUseNpcsByMappingVersionId   = collect();
        $this->inUseNpcIdsByMappingVersionId = collect();
    }

    /**
     * @inheritDoc
     */
    #[\Override]
    public function getInUseNpcs(MappingVersion $mappingVersion): Collection
    {
        if (!$this->inUseNpcsByMappingVersionId->has($mappingVersion->id)) {
            $inUseNpcs = parent::getInUseNpcs($mappingVersion);

            $this->inUseNpcsByMappingVersionId->put($mappingVersion->id, $inUseNpcs);
        }

        return $this->cloneCollection(
            $this->inUseNpcsByMappingVersionId->get($mappingVersion->id),
        );
    }

    /**
     * @inheritDoc
     */
    #[\Override]
    public function getInUseNpcIds(?MappingVersion $mappingVersion = null, ?Collection $inUseNpcs = null): Collection
    {
        if (!$this->inUseNpcIdsByMappingVersionId->has($mappingVersion->id)) {
            $inUseNpcIds = parent::getInUseNpcIds($mappingVersion, $inUseNpcs);

            $this->inUseNpcIdsByMappingVersionId->put($mappingVersion->id, $inUseNpcIds);
        }

        return $this->copyCollection(
            $this->inUseNpcIdsByMappingVersionId->get($mappingVersion->id),
        );
    }
}
