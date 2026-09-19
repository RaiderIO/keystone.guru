<?php

namespace App\Http\Requests\Team;

use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Team;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

/**
 * A team moderator adds one or more routes to the team in one go.
 */
class TeamAddRoutesFormRequest extends FormRequest
{
    public const int MAX_ROUTES_PER_REQUEST = 100;

    public function authorize(): bool
    {
        /** @var Team $team */
        $team = $this->route('team');

        return Gate::allows('moderate-route', $team);
    }

    /**
     * @return array<string, array<int, string|Rule>|string|Rule>
     */
    public function rules(): array
    {
        return [
            'dungeon_routes'   => ['required', 'array', 'min:1', sprintf('max:%d', self::MAX_ROUTES_PER_REQUEST)],
            'dungeon_routes.*' => ['required', 'string', Rule::exists(DungeonRoute::class, 'public_key')],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'dungeon_routes.required' => __('validation.custom.team_add_routes.required'),
            'dungeon_routes.min'      => __('validation.custom.team_add_routes.required'),
            'dungeon_routes.max'      => __('validation.custom.team_add_routes.max'),
            'dungeon_routes.*.exists' => __('validation.custom.team_add_routes.exists'),
        ];
    }

    /**
     * @return Collection<int, DungeonRoute> Every requested route once, whatever the number of times it was requested.
     */
    public function dungeonRoutes(): Collection
    {
        return once(fn(): Collection => DungeonRoute::query()
            ->with('author')
            ->whereIn('public_key', array_unique($this->validated('dungeon_routes')))
            ->get());
    }
}
