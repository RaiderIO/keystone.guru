<?php

namespace App\Http\Requests;

use App\Models\DungeonRoute\DungeonRoute;
use App\Models\UserPinnedDungeonRoute;
use App\Models\UserSocialLink;
use Closure;
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
    public function rules(): array
    {
        $userId = Auth::id() ?? 0;

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
                function (string $attribute, mixed $value, Closure $fail): void {
                    if ($value === null || $value === '') {
                        return;
                    }

                    $platform = last(explode('.', $attribute));

                    if (!UserSocialLink::isValidUrlForPlatform((string)$platform, (string)$value)) {
                        $fail(__('validation.custom.social_links.invalid_url_for_platform'));
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
                // user_pinned_dungeon_routes is unique on (user_id, dungeon_route_id): the select
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
            'bio.max'                        => __('validation.custom.bio.max'),
            'pinned_dungeon_routes.max'      => __('validation.custom.pinned_dungeon_routes.max'),
            'pinned_dungeon_routes.*.exists' => __('validation.custom.pinned_dungeon_routes.exists'),
        ];
    }

    /**
     * The social links to persist, keyed by platform, with blank entries stripped out.
     *
     * @return array<string, string>
     */
    public function socialLinks(): array
    {
        return once(function (): array {
            /** @var array<string, string|null> $submitted */
            $submitted = $this->validated('social_links') ?? [];

            $result = [];
            foreach ($submitted as $platform => $url) {
                if (!in_array($platform, UserSocialLink::ALL, true)) {
                    continue;
                }

                if ($url === null || trim($url) === '') {
                    continue;
                }

                $result[$platform] = trim($url);
            }

            return $result;
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
}
