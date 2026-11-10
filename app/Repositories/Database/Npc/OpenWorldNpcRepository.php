<?php

namespace App\Repositories\Database\Npc;

use App\Models\Npc\OpenWorldNpc;
use App\Repositories\Database\DatabaseRepository;
use App\Repositories\Interfaces\Npc\OpenWorldNpcRepositoryInterface;
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
}
