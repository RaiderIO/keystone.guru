<?php

namespace App\Http\Requests\DungeonRoute;

use App\Models\DungeonRoute\DungeonRoute;
use App\Models\DungeonRoute\DungeonRouteCollection;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;

/**
 * The shared shape of the requests that change which routes a collection holds: `dungeon_routes[]` carries route
 * public keys, and only the collection owner's own non-sandbox routes exist for it.
 */
abstract class AjaxDungeonRouteCollectionRoutesFormRequest extends FormRequest
{
    /**
     * Checked before validation, so someone who may not edit the collection learns nothing about its routes.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('edit', $this->dungeonRouteCollection()) ?? false;
    }

    public function dungeonRouteCollection(): DungeonRouteCollection
    {
        /** @var DungeonRouteCollection $dungeonRouteCollection */
        $dungeonRouteCollection = $this->route('dungeonRouteCollection');

        return $dungeonRouteCollection;
    }

    /**
     * The posted routes, in posted order.
     *
     * @return Collection<int, DungeonRoute>
     */
    public function dungeonRoutes(): Collection
    {
        return once(function (): Collection {
            /** @var array<int, string> $publicKeys */
            $publicKeys = $this->validated('dungeon_routes') ?? [];

            return $this->findDungeonRoutesInOrder($publicKeys);
        });
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'dungeon_routes.required'   => __('validation.custom.collection_dungeon_routes.required'),
            'dungeon_routes.max'        => __('validation.custom.collection_dungeon_routes.max'),
            'dungeon_routes.*.exists'   => __('validation.custom.collection_dungeon_routes.exists'),
            'dungeon_routes.*.distinct' => __('validation.custom.collection_dungeon_routes.distinct'),
        ];
    }

    /**
     * Only the collection owner's own routes may be in it, and a sandbox route expires, so it never is.
     */
    protected function ownDungeonRouteExistsRule(): Exists
    {
        return Rule::exists('dungeon_routes', 'public_key')
            ->where('author_id', $this->dungeonRouteCollection()->user_id)
            ->whereNull('expires_at');
    }

    /**
     * The public keys of the routes currently in the collection, in collection order.
     *
     * @return array<int, string>
     */
    protected function memberPublicKeys(): array
    {
        return once(fn(): array => DungeonRoute::query()
            ->join('dungeon_route_collection_routes', 'dungeon_route_collection_routes.dungeon_route_id', '=', 'dungeon_routes.id')
            ->where('dungeon_route_collection_routes.dungeon_route_collection_id', $this->dungeonRouteCollection()->id)
            ->orderBy('dungeon_route_collection_routes.order')
            ->pluck('dungeon_routes.public_key')
            ->all());
    }

    /**
     * @param  array<int, string>            $publicKeys
     * @return Collection<int, DungeonRoute>
     */
    protected function findDungeonRoutesInOrder(array $publicKeys): Collection
    {
        if ($publicKeys === []) {
            return collect();
        }

        $dungeonRoutes = DungeonRoute::query()
            ->with(['mappingVersion', 'dungeon'])
            ->whereIn('public_key', $publicKeys)
            ->get()
            ->keyBy('public_key');

        return collect($publicKeys)
            ->map(static fn(string $publicKey): ?DungeonRoute => $dungeonRoutes->get($publicKey))
            ->filter()
            ->values();
    }
}
