<?php

namespace App\Http\Requests\DungeonRoute;

use App\Models\DungeonRoute\DungeonRoute;
use App\Models\DungeonRoute\DungeonRouteCollection;
use App\Service\DungeonRoute\DungeonRouteCollectionServiceInterface;
use Illuminate\Validation\Validator;
use Override;

/**
 * Appends routes to a collection. Every route must be the owner's own, not in the collection yet, match the
 * collection's game version (and season, for a season set), and neither the collection nor any of its dungeons
 * may grow past its cap.
 */
class AjaxDungeonRouteCollectionRoutesAddFormRequest extends AjaxDungeonRouteCollectionRoutesFormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'dungeon_routes'   => ['required', 'array', sprintf('max:%d', DungeonRouteCollection::MAX_ROUTES)],
            'dungeon_routes.*' => ['required', 'string', 'distinct', $this->ownDungeonRouteExistsRule()],
        ];
    }

    #[Override]
    public function messages(): array
    {
        return array_merge(parent::messages(), [
            'dungeon_routes.max' => __('validation.custom.collection_dungeon_routes.max', ['max' => DungeonRouteCollection::MAX_ROUTES]),
        ]);
    }

    /**
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $dungeonRouteCollection = $this->dungeonRouteCollection();
                $dungeonRoutePublicKeys = $this->dungeonRoutePublicKeys();
                /** @var array<int, string> $publicKeys */
                $publicKeys    = $this->input('dungeon_routes');
                $dungeonRoutes = $this->findDungeonRoutesInOrder($publicKeys)->keyBy('public_key');

                foreach ($publicKeys as $index => $publicKey) {
                    $dungeonRoute = $dungeonRoutes->get($publicKey);
                    $key          = sprintf('dungeon_routes.%d', $index);

                    if ($dungeonRoute === null) {
                        continue;
                    }

                    if (in_array($publicKey, $dungeonRoutePublicKeys, true)) {
                        $validator->errors()->add($key, __('validation.custom.collection_dungeon_routes.already_in'));
                    } elseif (!$dungeonRouteCollection->mayContainDungeonRoute($dungeonRoute)) {
                        $isOfGameVersion = $dungeonRoute->mappingVersion !== null &&
                            $dungeonRoute->mappingVersion->game_version_id === $dungeonRouteCollection->game_version_id;

                        $validator->errors()->add($key, $isOfGameVersion
                            ? __('validation.custom.collection_dungeon_routes.season')
                            : __('validation.custom.collection_dungeon_routes.game_version'));
                    }
                }

                $joiningDungeonRoutes = collect($publicKeys)
                    ->map(static fn(string $publicKey): ?DungeonRoute => $dungeonRoutes->get($publicKey))
                    ->filter(static fn(?DungeonRoute $dungeonRoute): bool => $dungeonRoute !== null &&
                        !in_array($dungeonRoute->public_key, $dungeonRoutePublicKeys, true));
                $keptDungeonRoutes = DungeonRoute::query()
                    ->whereIn('public_key', $dungeonRoutePublicKeys)
                    ->get(['id', 'dungeon_id']);

                $overLimitDungeonRoutes = app(DungeonRouteCollectionServiceInterface::class)
                    ->getDungeonRoutesOverDungeonLimit($joiningDungeonRoutes, $keptDungeonRoutes);
                foreach ($overLimitDungeonRoutes as $index => $dungeonRoute) {
                    $validator->errors()->add(sprintf('dungeon_routes.%d', $index), __('validation.custom.collection_dungeon_routes.max_dungeon', [
                        'max'     => DungeonRouteCollection::MAX_ROUTES_PER_DUNGEON,
                        'dungeon' => __($dungeonRoute->dungeon->name),
                    ]));
                }

                if (count($dungeonRoutePublicKeys) + count($publicKeys) > DungeonRouteCollection::MAX_ROUTES) {
                    $validator->errors()->add('dungeon_routes', __('validation.custom.collection_dungeon_routes.max', [
                        'max' => DungeonRouteCollection::MAX_ROUTES,
                    ]));
                }
            },
        ];
    }
}
