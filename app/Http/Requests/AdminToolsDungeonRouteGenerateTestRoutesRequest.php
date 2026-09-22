<?php

namespace App\Http\Requests;

use App\Models\Dungeon;
use App\Models\Laratrust\Role;
use App\Models\PublishedState;
use App\Service\DungeonRoute\TestDungeonRouteGeneratorServiceInterface;
use Auth;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AdminToolsDungeonRouteGenerateTestRoutesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::user()->hasRole(Role::ROLE_ADMIN);
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'dungeon_id'      => ['required', 'integer', Rule::exists(Dungeon::class, 'id')],
            'count'           => ['required', 'integer', 'min:1', sprintf('max:%d', TestDungeonRouteGeneratorServiceInterface::MAX_ROUTES_PER_DUNGEON)],
            'published_state' => ['required', 'string', Rule::in(array_keys(PublishedState::ALL))],
        ];
    }

    public function getDungeon(): Dungeon
    {
        return once(fn() => Dungeon::findOrFail($this->validated('dungeon_id')));
    }
}
