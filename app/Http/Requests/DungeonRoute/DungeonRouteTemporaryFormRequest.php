<?php

namespace App\Http\Requests\DungeonRoute;

use App\Models\Dungeon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class DungeonRouteTemporaryFormRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Auth::user()->hasRole(["user", "admin"]);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $rules = [
            // Only active dungeons are allowed
            'dungeon_id' => ['required', Rule::exists(Dungeon::class, 'id')->where('active', '1')],

            'dungeon_difficulty' => Rule::in(Dungeon::DIFFICULTY_ALL),
        ];

        // Validate demo state, optional or numeric
        if (Auth::check() && Auth::user()->hasRole('admin')) {
            $rules['demo'] = 'numeric';
        }

        return $rules;
    }
}
