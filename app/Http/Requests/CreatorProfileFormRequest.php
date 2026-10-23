<?php

namespace App\Http\Requests;

use App\Models\DungeonRoute\DungeonRoute;
use App\Models\DungeonRoute\DungeonRouteCollection;
use App\Models\UserPinnedDungeonRoute;
use App\Models\UserPinnedDungeonRouteCollection;
use App\Models\UserSocialLink;
use App\Models\UserSocialLinkPlatform;
use App\Service\WebRisk\WebRiskServiceInterface;
use Closure;
use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class CreatorProfileFormRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(WebRiskServiceInterface $webRiskService): array
    {
        $userId = Auth::id() ?? 0;

        /** @var array<string, string> $storedSocialLinks Keyed by platform. */
        $storedSocialLinks = UserSocialLink::query()
            ->where('user_id', $userId)
            ->pluck('url', 'platform')
            ->all();

        return [
            'bio' => [
                'nullable',
                'string',
                'max:500',
            ],
            'hide_from_creator_directory' => [
                'nullable',
                'boolean',
            ],
            'social_links' => [
                'nullable',
                'array',
            ],
            // Only the platforms we know about may appear as keys
            'social_links.*' => [
                'nullable',
                'string',
                'max:255',
                function (string $attribute, mixed $value, Closure $fail) use ($webRiskService, $storedSocialLinks): void {
                    if ($value === null || $value === '') {
                        return;
                    }

                    $platformValue   = (string)last(explode('.', $attribute));
                    $platform        = UserSocialLinkPlatform::tryFrom($platformValue);
                    $normalizedValue = $platform?->normalize((string)$value);

                    if ($platform === null || $normalizedValue === null) {
                        $fail(__('validation.custom.social_links.invalid_for_platform'));

                        return;
                    }

                    // Only links new to this profile are looked up; a Discord username is not a link
                    if (($storedSocialLinks[$platformValue] ?? null) !== $normalizedValue &&
                        $platform->isValidUrl($normalizedValue) &&
                        $webRiskService->isThreat($normalizedValue)) {
                        $fail(__('validation.custom.social_links.unsafe'));
                    }
                },
            ],
            'pinned_dungeon_routes' => [
                'nullable',
                'array',
                sprintf('max:%d', UserPinnedDungeonRoute::MAX_PINNED_ROUTES),
            ],
            // A user may only ever pin their own routes - without the author_id constraint anyone
            // could pin (and thereby surface) a route belonging to someone else
            'pinned_dungeon_routes.*' => [
                'required',
                'integer',
                // user_pinned_dungeon_routes is unique on (user_id, dungeon_route_id): the form
                // cannot produce duplicates, but a hand-crafted post could, and the insert would
                // then fail on the constraint rather than as a validation error
                'distinct',
                Rule::exists('dungeon_routes', 'id')
                    ->where('author_id', $userId)
                    // Sandbox routes expire, so they are deliberately not offered as pinnable
                    ->where(static fn(Builder $query) => $query->where('expires_at', 0)->orWhereNull('expires_at')),
            ],
            'pinned_dungeon_route_collections' => [
                'nullable',
                'array',
                sprintf('max:%d', UserPinnedDungeonRouteCollection::MAX_PINNED_COLLECTIONS),
            ],
            // Same constraint as the pinned routes above: a user may only pin collections they own
            'pinned_dungeon_route_collections.*' => [
                'required',
                'integer',
                'distinct',
                Rule::exists('dungeon_route_collections', 'id')
                    ->where('user_id', $userId),
            ],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'bio.max'                                   => __('validation.custom.bio.max'),
            'pinned_dungeon_routes.max'                 => __('validation.custom.pinned_dungeon_routes.max'),
            'pinned_dungeon_routes.*.exists'            => __('validation.custom.pinned_dungeon_routes.exists'),
            'pinned_dungeon_route_collections.max'      => __('validation.custom.pinned_dungeon_route_collections.max'),
            'pinned_dungeon_route_collections.*.exists' => __('validation.custom.pinned_dungeon_route_collections.exists'),
        ];
    }

    /**
     * The social links to persist, keyed by platform and normalized to what is stored, with blank entries stripped out.
     *
     * @return array<string, string>
     */
    public function socialLinks(): array
    {
        return once(function (): array {
            /** @var array<string, string|null> $submitted */
            $submitted = $this->validated('social_links') ?? [];

            $result = [];
            foreach ($submitted as $platform => $value) {
                $normalizedValue = UserSocialLinkPlatform::tryFrom((string)$platform)?->normalize((string)$value);

                if ($normalizedValue === null) {
                    continue;
                }

                $result[$platform] = $normalizedValue;
            }

            return $result;
        });
    }

    /**
     * The collections to pin, in submitted order, already constrained to the current user's own
     * collections.
     *
     * @return Collection<int, DungeonRouteCollection>
     */
    public function pinnedDungeonRouteCollections(): Collection
    {
        return once(function (): Collection {
            /** @var array<int, int> $ids */
            $ids = $this->validated('pinned_dungeon_route_collections') ?? [];

            if ($ids === []) {
                return collect();
            }

            $dungeonRouteCollections = DungeonRouteCollection::query()
                ->whereIn('id', $ids)
                ->get()
                ->keyBy('id');

            // Preserve the order the user submitted them in, which becomes the display order
            return collect($ids)
                ->map(static fn(int $id): ?DungeonRouteCollection => $dungeonRouteCollections->get($id))
                ->filter()
                ->values();
        });
    }

    /**
     * The routes to pin, in submitted order, already constrained to the current user's own routes.
     *
     * @return Collection<int, DungeonRoute>
     */
    public function pinnedDungeonRoutes(): Collection
    {
        return once(function (): Collection {
            /** @var array<int, int> $ids */
            $ids = $this->validated('pinned_dungeon_routes') ?? [];

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

    /**
     * Back to the Creator tab rather than the page's first tab, where the errors would be out of sight.
     */
    protected function getRedirectUrl(): string
    {
        return sprintf('%s#creator', route('profile.edit'));
    }
}
