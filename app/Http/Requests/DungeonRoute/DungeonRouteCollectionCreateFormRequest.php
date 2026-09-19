<?php

namespace App\Http\Requests\DungeonRoute;

use App\Models\GameVersion\GameVersion;
use App\Models\Season;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Fluent;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * The query of the new-collection form: which game version and season it opens with, so its route picker can be
 * rebuilt for whatever the user picks.
 */
class DungeonRouteCollectionCreateFormRequest extends FormRequest
{
    /**
     * The season_id value that selects a free-form collection.
     */
    public const string SEASON_NONE = 'none';

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
            'game_version_id' => [
                'nullable',
                'integer',
                Rule::exists('game_versions', 'id')->where('active', 1),
            ],
            'season_id' => [
                'nullable',
                Rule::when(
                    static fn(Fluent $input): bool => $input->get('season_id') !== self::SEASON_NONE,
                    ['integer', Rule::exists('seasons', 'id')],
                ),
            ],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'game_version_id.exists' => __('validation.custom.collection_game_version_id.exists'),
            'season_id.exists'       => __('validation.custom.collection_season_id.exists'),
        ];
    }

    /**
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->hasAny(['game_version_id', 'season_id'])) {
                    return;
                }

                $season = $this->season();
                if ($season === null) {
                    return;
                }

                $gameVersion = $this->gameVersion() ?? GameVersion::getUserOrDefaultGameVersion();
                if (!$gameVersion->has_seasons) {
                    $validator->errors()->add('season_id', __('validation.custom.collection_season_id.no_seasons'));
                } elseif ($season->expansion_id !== $gameVersion->expansion_id) {
                    $validator->errors()->add('season_id', __('validation.custom.collection_season_id.expansion'));
                }
            },
        ];
    }

    /**
     * The requested game version, if any.
     */
    public function gameVersion(): ?GameVersion
    {
        return once(function (): ?GameVersion {
            $gameVersionId = $this->query('game_version_id');

            return $gameVersionId === null ? null : GameVersion::query()->findOrFail((int)$gameVersionId);
        });
    }

    /**
     * Whether a season (or explicitly none) was requested.
     */
    public function hasSeason(): bool
    {
        return $this->query('season_id') !== null;
    }

    /**
     * The requested season; null when none or free-form was requested.
     */
    public function season(): ?Season
    {
        return once(function (): ?Season {
            $seasonId = $this->query('season_id');

            if ($seasonId === null || $seasonId === self::SEASON_NONE) {
                return null;
            }

            return Season::query()->findOrFail((int)$seasonId);
        });
    }
}
