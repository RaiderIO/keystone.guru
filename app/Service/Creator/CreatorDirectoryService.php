<?php

namespace App\Service\Creator;

use App\Models\PublishedState;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class CreatorDirectoryService implements CreatorDirectoryServiceInterface
{
    /** @return LengthAwarePaginator<int, User> */
    public function paginateCreators(?string $search = null, ?int $perPage = null): LengthAwarePaginator
    {
        $perPage ??= (int)config('keystoneguru.creators.per_page');

        return $this->buildListedCreatorsQuery()
            ->when(
                $search !== null && $search !== '',
                static fn(Builder $builder): Builder => $builder->where(
                    'name',
                    'like',
                    sprintf('%%%s%%', addcslashes((string)$search, '%_\\')),
                ),
            )
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * Creators eligible for the directory, ordered by how many routes they have published.
     *
     * Listing is automatic above a threshold and opt-out, so the only people excluded are those
     * below the bar and those who ticked hide_from_creator_directory.
     *
     * The route count is produced by a single withCount subquery and filtered with having() rather
     * than a second whereHas subquery, so the threshold and the displayed count cannot drift apart.
     *
     * @return Builder<User>
     */
    private function buildListedCreatorsQuery(): Builder
    {
        $minPublishedRoutes = (int)config('keystoneguru.creators.min_published_routes');
        $worldPublishedId   = PublishedState::ALL[PublishedState::WORLD];

        return User::query()
            ->where('hide_from_creator_directory', false)
            ->withCount([
                'dungeonRoutes as published_route_count' => static fn($query) => $query
                    ->where('published_state_id', $worldPublishedId),
            ])
            // The route cards and creator cards both render the avatar
            ->with(['iconfile'])
            ->having('published_route_count', '>=', $minPublishedRoutes)
            ->orderByDesc('published_route_count')
            // Stable tiebreak so pagination cannot repeat or skip a creator between pages
            ->orderBy('id');
    }
}
