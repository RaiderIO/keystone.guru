<?php

namespace App\Http\Requests\DungeonRoute;

use App\Models\DungeonRoute\DungeonRouteCollection;
use Illuminate\Validation\Validator;
use Override;

/**
 * Appends routes to a collection. Every route must be the owner's own, not in the collection yet, match the
 * collection's game version (and season, for a season set), and the collection may not grow past its cap.
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
                $memberPublicKeys       = $this->memberPublicKeys();
                /** @var array<int, string> $publicKeys */
                $publicKeys    = $this->input('dungeon_routes');
                $dungeonRoutes = $this->findDungeonRoutesInOrder($publicKeys)->keyBy('public_key');

                foreach ($publicKeys as $index => $publicKey) {
                    $dungeonRoute = $dungeonRoutes->get($publicKey);
                    $key          = sprintf('dungeon_routes.%d', $index);

                    if ($dungeonRoute === null) {
                        continue;
                    }

                    if (in_array($publicKey, $memberPublicKeys, true)) {
                        $validator->errors()->add($key, __('validation.custom.collection_dungeon_routes.already_in'));
                    } elseif (!$dungeonRouteCollection->mayContainDungeonRoute($dungeonRoute)) {
                        $isOfGameVersion = $dungeonRoute->mappingVersion !== null &&
                            $dungeonRoute->mappingVersion->game_version_id === $dungeonRouteCollection->game_version_id;

                        $validator->errors()->add($key, $isOfGameVersion
                            ? __('validation.custom.collection_dungeon_routes.season')
                            : __('validation.custom.collection_dungeon_routes.game_version'));
                    }
                }

                if (count($memberPublicKeys) + count($publicKeys) > DungeonRouteCollection::MAX_ROUTES) {
                    $validator->errors()->add('dungeon_routes', __('validation.custom.collection_dungeon_routes.max', [
                        'max' => DungeonRouteCollection::MAX_ROUTES,
                    ]));
                }
            },
        ];
    }
}
