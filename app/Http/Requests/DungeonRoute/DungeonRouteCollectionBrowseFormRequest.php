<?php

namespace App\Http\Requests\DungeonRoute;

use App\Models\Dungeon;
use App\Models\DungeonRoute\DungeonRouteCollection;
use App\Models\DungeonRoute\DungeonRouteCollectionCategory;
use Illuminate\Foundation\Http\FormRequest;

class DungeonRouteCollectionBrowseFormRequest extends FormRequest
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
        return [
            'search' => [
                'nullable',
                'string',
                sprintf('max:%d', DungeonRouteCollection::MAX_NAME_LENGTH),
            ],
            'category_id' => [
                'nullable',
                'integer',
                'exists:dungeon_route_collection_categories,id',
            ],
            'dungeon' => [
                'nullable',
                'string',
                'exists:dungeons,slug',
            ],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'search.max'         => __('validation.custom.collection_browse_search.max'),
            'category_id.exists' => __('validation.custom.collection_category_id.exists'),
            'dungeon.exists'     => __('validation.custom.collection_browse_dungeon.exists'),
        ];
    }

    /**
     * The category to filter on, or null when browsing every category.
     */
    public function dungeonRouteCollectionCategory(): ?DungeonRouteCollectionCategory
    {
        return once(function (): ?DungeonRouteCollectionCategory {
            $categoryId = $this->validated('category_id');

            if ($categoryId === null) {
                return null;
            }

            return DungeonRouteCollectionCategory::query()->findOrFail($categoryId);
        });
    }

    /**
     * The dungeon to filter on, or null when browsing every dungeon.
     */
    public function dungeon(): ?Dungeon
    {
        return once(function (): ?Dungeon {
            $slug = $this->validated('dungeon');

            if ($slug === null) {
                return null;
            }

            return Dungeon::query()->where('slug', $slug)->firstOrFail();
        });
    }

    /**
     * The trimmed search term, or null when browsing unfiltered.
     */
    public function search(): ?string
    {
        return once(function (): ?string {
            $search = $this->validated('search');

            if ($search === null) {
                return null;
            }

            $search = trim((string)$search);

            return $search === '' ? null : $search;
        });
    }
}
