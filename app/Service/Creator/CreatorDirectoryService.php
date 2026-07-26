<?php

namespace App\Service\Creator;

use App\Models\User;
use App\Repositories\Interfaces\UserRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class CreatorDirectoryService implements CreatorDirectoryServiceInterface
{
    public function __construct(private readonly UserRepositoryInterface $userRepository)
    {
    }

    /** @return LengthAwarePaginator<int, User> */
    public function paginateCreators(?string $search = null, ?int $perPage = null): LengthAwarePaginator
    {
        $perPage ??= (int)config('keystoneguru.creators.per_page');

        return $this->userRepository->buildListedCreatorsQuery()
            ->when(
                $search !== null && $search !== '',
                static fn(Builder $builder): Builder => $builder->where(
                    'users.name',
                    'like',
                    sprintf('%%%s%%', addcslashes((string)$search, '%_\\')),
                ),
            )
            ->paginate($perPage)
            ->withQueryString();
    }

    /** @return Collection<int, User> */
    public function getFeaturedCreators(?int $limit = null): Collection
    {
        $limit ??= (int)config('keystoneguru.creators.featured_count');

        return $this->userRepository->buildListedCreatorsQuery()
            ->limit($limit)
            ->get();
    }
}
