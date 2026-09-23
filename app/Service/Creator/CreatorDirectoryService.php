<?php

namespace App\Service\Creator;

use App\Models\Dungeon;
use App\Models\GameVersion\GameVersion;
use App\Models\Season;
use App\Models\User;
use App\Repositories\Interfaces\UserRepositoryInterface;
use App\Service\Cache\CacheServiceInterface;
use App\Service\Creator\Dtos\CreatorStats;
use App\Service\Creator\Enums\CreatorDirectorySort;
use App\Service\Season\SeasonServiceInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class CreatorDirectoryService implements CreatorDirectoryServiceInterface
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly CacheServiceInterface   $cacheService,
        private readonly SeasonServiceInterface  $seasonService,
    ) {
    }

    /** @return LengthAwarePaginator<int, User> */
    public function paginateCreators(
        ?string              $search = null,
        ?int                 $categoryId = null,
        CreatorDirectorySort $sort = CreatorDirectorySort::ActiveThisSeason,
        ?int                 $perPage = null,
    ): LengthAwarePaginator {
        $perPage ??= (int)config('keystoneguru.creators.per_page');

        return $this->userRepository->buildListedCreatorsQuery($categoryId, $this->getStatsSeason()?->id, $sort)
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
     * Cached, unlike paginateCreators(): the listed-creators aggregate is a GROUP BY over every
     * world-published route, which materialises in full before the LIMIT can take a few rows off
     * it, and the rail renders on the per-dungeon route page - one of the busiest on the site - on
     * every page of results. It does not vary by viewer, so the dungeon and season are the whole key.
     *
     * @return Collection<int, User>
     */
    public function getFeaturedCreators(Dungeon $dungeon, ?int $limit = null): Collection
    {
        $limit ??= (int)config('keystoneguru.creators.featured_count');
        $season = $this->seasonService->getCurrentSeasonForDungeon($dungeon);

        return $this->cacheService->remember(
            sprintf('creators:featured:%d:%d:%d', $dungeon->id, $season->id ?? 0, $limit),
            function () use ($dungeon, $season, $limit): Collection {
                $featuredCreators = $this->userRepository->buildFeaturedCreatorsForDungeonQuery($dungeon->id, $season?->id)
                    ->limit($limit)
                    ->get();

                // One creator is not a selection; the rail would read as an advert for them
                return $featuredCreators->count() < (int)config('keystoneguru.creators.featured_min_count')
                    ? new Collection()
                    : $featuredCreators;
            },
            config('keystoneguru.creators.featured_ttl'),
        );
    }

    public function getCreatorStats(User $user): CreatorStats
    {
        $season = $this->getStatsSeason();

        return CreatorStats::fromAttributes(
            $this->userRepository->getCreatorStatsAttributes($user->id, $season?->id),
            $season,
        );
    }

    public function getStatsSeason(): ?Season
    {
        return GameVersion::getUserOrDefaultGameVersion()->has_seasons
            ? $this->seasonService->getCurrentSeason()
            : null;
    }
}
