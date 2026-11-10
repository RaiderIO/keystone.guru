<?php

namespace App\Repositories\Database\Npc;

use App\Models\Npc\OpenWorldNpc;
use App\Repositories\Database\DatabaseRepository;
use App\Repositories\Interfaces\Npc\OpenWorldNpcRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class OpenWorldNpcRepository extends DatabaseRepository implements OpenWorldNpcRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(OpenWorldNpc::class);
    }

    public function findAllByIdKeyed(Collection $ids): Collection
    {
        return $ids
            ->chunk(1000)
            ->flatMap(static fn(Collection $chunk) => OpenWorldNpc::query()->whereIn('id', $chunk->values())->get())
            ->keyBy('id');
    }

    public function getWithoutDisplayId(?int $limit = null): Collection
    {
        return OpenWorldNpc::query()
            ->whereNull('display_id')
            ->orderBy('id')
            ->when($limit !== null, static fn($query) => $query->limit($limit))
            ->get();
    }

    public function getWithoutPortrait(): Collection
    {
        return OpenWorldNpc::query()
            ->where('has_portrait', false)
            ->orderBy('id')
            ->get();
    }

    public function getPortraitCandidates(int $limit, bool $includeUnrenderable): Collection
    {
        return $this->portraitCandidatesQuery($includeUnrenderable)
            ->orderBy('id')
            ->limit($limit)
            ->get();
    }

    public function countPortraitCandidates(bool $includeUnrenderable): int
    {
        return $this->portraitCandidatesQuery($includeUnrenderable)->count();
    }

    public function getUnrenderablePortraits(): Collection
    {
        return OpenWorldNpc::query()
            ->where('portrait_unrenderable', true)
            ->orderBy('id')
            ->get();
    }

    public function updatePortraitState(Collection $ids, bool $hasPortrait, bool $portraitUnrenderable): int
    {
        return $ids
            ->chunk(1000)
            ->sum(static fn(Collection $chunk) => OpenWorldNpc::query()
                ->whereIn('id', $chunk->values())
                ->update([
                    'has_portrait'          => $hasPortrait,
                    'portrait_unrenderable' => $portraitUnrenderable,
                ]));
    }

    /**
     * @return Builder<OpenWorldNpc>
     */
    private function portraitCandidatesQuery(bool $includeUnrenderable): Builder
    {
        return OpenWorldNpc::query()
            ->where('has_portrait', false)
            ->whereNotNull('display_id')
            ->when(!$includeUnrenderable, static fn(Builder $query) => $query->where('portrait_unrenderable', false));
    }
}
