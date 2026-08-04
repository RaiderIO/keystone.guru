<?php

namespace App\Service\Creator;

use App\Models\User;
use App\Repositories\Interfaces\UserRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

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
}
