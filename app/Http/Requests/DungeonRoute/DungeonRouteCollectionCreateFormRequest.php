<?php

namespace App\Http\Requests\DungeonRoute;

use App\Models\GameVersion\GameVersion;
use App\Models\Season;
use App\Service\GameVersion\GameVersionServiceInterface;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Fluent;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * The query of the new-collection form: which season it opens with, so its route picker can be rebuilt for whatever
 * season the user picks. The game version is always the one selected on the site.
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
                if ($validator->errors()->has('season_id')) {
                    return;
                }

                $season = $this->season();
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

    /**
     * The game version selected on the site, which every new collection is for.
     */
    public function gameVersion(): GameVersion
    {
        return once(function (): GameVersion {
            /** @var GameVersionServiceInterface $gameVersionService */
            $gameVersionService = app(GameVersionServiceInterface::class);

            return $gameVersionService->getGameVersion(Auth::user());
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
