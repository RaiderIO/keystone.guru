<?php

namespace App\Http\Requests\DungeonRoute;

use Illuminate\Validation\Validator;

/**
 * Removes routes from a collection; every route must currently be in it.
 */
class AjaxDungeonRouteCollectionRoutesRemoveFormRequest extends AjaxDungeonRouteCollectionRoutesFormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'dungeon_routes'   => ['required', 'array'],
            'dungeon_routes.*' => ['required', 'string', 'distinct', $this->ownDungeonRouteExistsRule()],
        ];
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

                $dungeonRoutePublicKeys = $this->dungeonRoutePublicKeys();

                /** @var array<int, string> $publicKeys */
                $publicKeys = $this->input('dungeon_routes');
                foreach ($publicKeys as $index => $publicKey) {
                    if (!in_array($publicKey, $dungeonRoutePublicKeys, true)) {
                        $validator->errors()->add(
                            sprintf('dungeon_routes.%d', $index),
                            __('validation.custom.collection_dungeon_routes.not_in'),
                        );
                    }
                }
            },
        ];
    }
}
