<?php

namespace App\Http\Requests;

use App\Models\DungeonRoute\DungeonRouteCollectionCategory;
use App\Service\Creator\Enums\CreatorDirectorySort;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreatorDirectoryFormRequest extends FormRequest
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
            // Usernames are capped at 24 characters, so anything longer cannot match a creator
            'search' => [
                'nullable',
                'string',
                'max:24',
            ],
            'category_id' => [
                'nullable',
                'integer',
                'exists:dungeon_route_collection_categories,id',
            ],
            'sort' => [
                'nullable',
                Rule::enum(CreatorDirectorySort::class),
            ],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'search.max'         => __('validation.custom.creator_search.max'),
            'category_id.exists' => __('validation.custom.collection_category_id.exists'),
            'sort'               => __('validation.custom.creator_sort.enum'),
        ];
    }

    public function sort(): CreatorDirectorySort
    {
        return CreatorDirectorySort::tryFrom((string)$this->validated('sort')) ?? CreatorDirectorySort::ActiveThisSeason;
    }

    /**
     * The category to filter the directory on, or null when the user is browsing every category.
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
     * The trimmed search term, or null when the user is browsing unfiltered.
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
