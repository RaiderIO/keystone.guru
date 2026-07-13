<?php

namespace App\Http\Requests\DungeonRoute;

use App\Models\Dungeon;
use App\Models\Laratrust\Role;
use App\Rules\DungeonRouteLevelRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class DungeonRouteSubmitTemporaryFormRequest extends FormRequest
{
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
        $rules = [
            // Only active dungeons are allowed
            'dungeon_id' => [
                'required',
                Rule::exists(Dungeon::class, 'id')->where('active', '1'),
            ],
            // Nullable: the difficulty select is empty for non-speedrun dungeons, and Tom Select submits an empty value for it
            'dungeon_difficulty'  => ['nullable', Rule::in(array_values(Dungeon::DIFFICULTY_ALL))],
            'dungeon_route_level' => new DungeonRouteLevelRule(),

            // Verified against the dungeon's mapping version in DungeonRouteSaveService
            'dungeon_start_map_icon_id' => 'nullable|integer',
        ];

        // Validate demo state, optional or numeric
        if (Auth::user()?->hasRole(Role::ROLE_ADMIN)) {
            $rules['demo'] = 'numeric';
        }

        return $rules;
    }
}
