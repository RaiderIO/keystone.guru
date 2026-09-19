<?php

namespace App\Http\Requests\DungeonRoute;

use App\Models\DungeonRoute\DungeonRouteCollection;
use App\Models\GameVersion\GameVersion;
use App\Models\Season;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Duplicates a collection into a new one of the same game version, with the season chosen here. Only the owner may
 * duplicate a collection: its routes are theirs, and a collection only holds its owner's own routes.
 */
class DungeonRouteCollectionDuplicateFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->dungeonRouteCollection()->isOwnedByUser($this->user());
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            // Null makes the duplicate free-form. Whether the season fits the game version is checked in after()
            'season_id' => [
                'nullable',
                'integer',
                Rule::exists('seasons', 'id'),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'season_id.exists' => __('validation.custom.collection_season_id.exists'),
        ];
    }

    /**
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $season = $validator->errors()->has('season_id') ? null : $this->season();
                if ($season === null) {
                    return;
                }

                $gameVersion = $this->gameVersion();
                if (!$gameVersion->has_seasons) {
                    $validator->errors()->add('season_id', __('validation.custom.collection_season_id.no_seasons'));
                } elseif ($season->expansion_id !== $gameVersion->expansion_id) {
                    $validator->errors()->add('season_id', __('validation.custom.collection_season_id.expansion'));
                }
            },
        ];
    }

    public function dungeonRouteCollection(): DungeonRouteCollection
    {
        /** @var DungeonRouteCollection $dungeonRouteCollection */
        $dungeonRouteCollection = $this->route('dungeonRouteCollection');

        return $dungeonRouteCollection;
    }

    /**
     * The duplicate keeps the game version of the collection it copies.
     */
    public function gameVersion(): GameVersion
    {
        return once(fn(): GameVersion => $this->dungeonRouteCollection()->gameVersion ?? GameVersion::getDefaultGameVersion());
    }

    public function season(): ?Season
    {
        return once(function (): ?Season {
            $seasonId = $this->input('season_id');

            return $seasonId === null ? null : Season::query()->with(['expansion'])->findOrFail((int)$seasonId);
        });
    }
}
