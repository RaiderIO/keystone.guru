<?php

namespace App\Http\Requests\DungeonRoute;

use Illuminate\Validation\Validator;

/**
 * Puts the routes of a collection in a new order. The posted list must hold exactly the routes that are in the
 * collection: nothing foreign, nothing missing.
 */
class AjaxDungeonRouteCollectionRoutesOrderFormRequest extends AjaxDungeonRouteCollectionRoutesFormRequest
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

                $memberPublicKeys = $this->memberPublicKeys();

                /** @var array<int, string> $publicKeys */
                $publicKeys = $this->input('dungeon_routes');
                foreach ($publicKeys as $index => $publicKey) {
                    if (!in_array($publicKey, $memberPublicKeys, true)) {
                        $validator->errors()->add(
                            sprintf('dungeon_routes.%d', $index),
                            __('validation.custom.collection_dungeon_routes.not_in'),
                        );
                    }
                }

                if (array_diff($memberPublicKeys, $publicKeys) !== []) {
                    $validator->errors()->add('dungeon_routes', __('validation.custom.collection_dungeon_routes.missing'));
                }
            },
        ];
    }
}
