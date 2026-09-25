<?php

namespace App\Http\Requests\DungeonRoute;

use App\Models\DungeonRoute\DungeonRoute;
use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;

/**
 * Deletes several routes at once; `dungeon_routes[]` carries their public keys. Whether the caller may delete
 * each of them is the controller's gate check, not this request's.
 */
class AjaxDungeonRouteDeleteBulkFormRequest extends FormRequest
{
    /**
     * Each route costs a dozen-plus queries across two connections plus its thumbnail files, so one request
     * only ever deletes this many; the drawer sends consecutive batches beyond it.
     */
    public const int MAX_DUNGEON_ROUTES = 100;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * The posted routes, with the relations the delete path reads on a collection of routes.
     *
     * @return Collection<int, DungeonRoute>
     */
    public function dungeonRoutes(): Collection
    {
        return once(function (): Collection {
            /** @var array<int, string> $publicKeys */
            $publicKeys = $this->validated('dungeon_routes') ?? [];

            if ($publicKeys === []) {
                return collect();
            }

            return DungeonRoute::query()
                ->with(['team'])
                ->whereIn('public_key', $publicKeys)
                ->get();
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'dungeon_routes'   => ['required', 'array', sprintf('max:%d', self::MAX_DUNGEON_ROUTES)],
            'dungeon_routes.*' => [
                'required',
                'string',
                'distinct',
                Rule::exists('dungeon_routes', 'public_key')
                    // A sandbox route expires on its own and is nobody's to delete; the column is 0 on some
                    // rows and NULL on others, the same pair the route listing matches on
                    ->where(static fn(Builder $query) => $query->where('expires_at', 0)->orWhereNull('expires_at')),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'dungeon_routes.required'   => __('validation.custom.delete_bulk_dungeon_routes.required'),
            'dungeon_routes.max'        => __('validation.custom.delete_bulk_dungeon_routes.max'),
            'dungeon_routes.*.exists'   => __('validation.custom.delete_bulk_dungeon_routes.exists'),
            'dungeon_routes.*.distinct' => __('validation.custom.delete_bulk_dungeon_routes.distinct'),
        ];
    }
}
