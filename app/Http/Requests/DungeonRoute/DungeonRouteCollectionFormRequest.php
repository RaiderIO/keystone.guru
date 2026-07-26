<?php

namespace App\Http\Requests\DungeonRoute;

use App\Models\DungeonRoute\DungeonRoute;
use App\Models\DungeonRoute\DungeonRouteCollection;
use App\Models\PublishedState;
use App\Models\Team;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class DungeonRouteCollectionFormRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $userId = Auth::id() ?? 0;

        return [
            'name' => [
                'required',
                'string',
                'max:128',
            ],
            'description' => [
                'nullable',
                'string',
                'max:1000',
            ],
            'published_state' => [
                'required',
                'string',
                Rule::in(DungeonRouteCollection::AVAILABLE_PUBLISHED_STATES),
            ],
            // A collection may only be shared with a team the user is actually a member of
            'team_id' => [
                'nullable',
                'integer',
                sprintf('required_if:published_state,%s', PublishedState::TEAM),
                Rule::exists('team_users', 'team_id')
                    ->where('user_id', $userId),
            ],
            'dungeon_routes' => [
                'nullable',
                'array',
                sprintf('max:%d', DungeonRouteCollection::MAX_ROUTES),
            ],
            // A user may only collect their own routes - without the author_id constraint anyone
            // could put (and thereby surface) someone else's route in their collection
            'dungeon_routes.*' => [
                'required',
                'integer',
                // dungeon_route_collection_routes is unique on (collection, route): the select
                // cannot produce duplicates, but a hand-crafted post could, and the insert would
                // then fail on the constraint rather than as a validation error
                'distinct',
                Rule::exists('dungeon_routes', 'id')
                    ->where('author_id', $userId),
            ],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'name.required'             => __('validation.custom.collection_name.required'),
            'name.max'                  => __('validation.custom.collection_name.max'),
            'description.max'           => __('validation.custom.collection_description.max'),
            'team_id.required_if'       => __('validation.custom.collection_team_id.required_if'),
            'team_id.exists'            => __('validation.custom.collection_team_id.exists'),
            'dungeon_routes.max'        => __('validation.custom.collection_dungeon_routes.max'),
            'dungeon_routes.*.exists'   => __('validation.custom.collection_dungeon_routes.exists'),
            'dungeon_routes.*.distinct' => __('validation.custom.collection_dungeon_routes.distinct'),
        ];
    }

    /**
     * The published state to persist, as an id.
     */
    public function publishedStateId(): int
    {
        return PublishedState::ALL[$this->validated('published_state')];
    }

    /**
     * The team this collection is shared with, if any. Only relevant for the team published state -
     * a collection that is not team published never keeps a team behind.
     */
    public function team(): ?Team
    {
        return once(function (): ?Team {
            $teamId = $this->validated('team_id');

            if ($teamId === null || $this->validated('published_state') !== PublishedState::TEAM) {
                return null;
            }

            return Team::query()->findOrFail($teamId);
        });
    }

    /**
     * The routes to collect, in submitted order, already constrained to the current user's own
     * routes.
     *
     * @return Collection<int, DungeonRoute>
     */
    public function dungeonRoutes(): Collection
    {
        return once(function (): Collection {
            /** @var array<int, int> $ids */
            $ids = $this->validated('dungeon_routes') ?? [];

            if ($ids === []) {
                return collect();
            }

            $dungeonRoutes = DungeonRoute::query()
                ->whereIn('id', $ids)
                ->get()
                ->keyBy('id');

            // Preserve the order the user submitted them in, which becomes the display order
            return collect($ids)
                ->map(static fn(int $id): ?DungeonRoute => $dungeonRoutes->get($id))
                ->filter()
                ->values();
        });
    }
}
