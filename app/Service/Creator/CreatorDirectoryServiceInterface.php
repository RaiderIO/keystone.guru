<?php

namespace App\Service\Creator;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface CreatorDirectoryServiceInterface
{
    /**
     * A page of listed creators, most published routes first.
     *
     * @param string|null $search Optional case-insensitive match on the creator's name.
     *
     * @return LengthAwarePaginator<int, User>
     */
    public function paginateCreators(?string $search = null, ?int $perPage = null): LengthAwarePaginator;

    /**
     * The creators to surface in the featured strip on the per-dungeon route page.
     *
     * @return Collection<int, User>
     */
    public function getFeaturedCreators(?int $limit = null): Collection;
}
