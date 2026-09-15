<?php

namespace App\Service\Creator;

use App\Models\User;
use App\Repositories\Interfaces\UserRepositoryInterface;
use App\Service\Cache\CacheServiceInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class CreatorDirectoryService implements CreatorDirectoryServiceInterface
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly CacheServiceInterface   $cacheService,
    ) {
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

    /**
     * Cached, unlike paginateCreators(): buildListedCreatorsQuery() derives its published route
     * counts with a GROUP BY over the whole dungeon_routes table, which materialises in full before
     * the LIMIT can take six rows off it. The rail renders on the per-dungeon route page - one of
     * the busiest on the site - and, unlike the hero band, on every page of results, not just the
     * first.
     *
     * The result does not vary by viewer, dungeon or page, so there is nothing to key on and no
     * invalidation question: it is a site-wide list that goes stale gracefully within the TTL.
     * CacheService::remember() is a passthrough while caching is disabled (development, tests).
     *
     * @return Collection<int, User>
     */
    public function getFeaturedCreators(?int $limit = null): Collection
    {
        $limit ??= (int)config('keystoneguru.creators.featured_count');

        return $this->cacheService->remember(
            sprintf('creators:featured:%d', $limit),
            fn(): Collection => $this->userRepository->buildListedCreatorsQuery()
                ->limit($limit)
                ->get(),
            config('keystoneguru.creators.featured_ttl'),
        );
    }
}
