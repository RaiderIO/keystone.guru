<?php

namespace App\Service\Creator;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

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
}
