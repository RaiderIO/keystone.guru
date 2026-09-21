<?php

namespace App\Http\Requests\DungeonRoute;

use App\Models\DungeonRoute\DungeonRoute;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Lists the current user's collections for one of their own routes. Someone else's route, or a sandbox route, does
 * not exist for this request.
 */
class AjaxDungeonRouteCollectionsForRouteFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'dungeon_route' => [
                'required',
                'string',
                Rule::exists('dungeon_routes', 'public_key')
                    ->where('author_id', $this->user()->id ?? 0)
                    ->whereNull('expires_at'),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'dungeon_route.exists' => __('validation.custom.collection_dungeon_routes.exists'),
        ];
    }

    public function dungeonRoute(): DungeonRoute
    {
        return once(fn(): DungeonRoute => DungeonRoute::query()
            ->with(['mappingVersion'])
            ->where('public_key', $this->validated('dungeon_route'))
            ->firstOrFail());
    }
}
