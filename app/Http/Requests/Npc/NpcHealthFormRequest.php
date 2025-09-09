<?php

namespace App\Http\Requests\Npc;

use App\Models\GameVersion\GameVersion;
use App\Models\Laratrust\Role;
use Auth;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class NpcHealthFormRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Auth::user()->hasRole(Role::ROLE_ADMIN);
    }

    protected function prepareForValidation(): void
    {
        if ((int)($this->input('percentage')) === 100) {
            $this->merge([
                'percentage' => null,
            ]);
        }

        // Remove commas or dots in the name; we want the integer value
        $this->merge([
            'health' => str_replace([
                ',',
                '.',
            ], '', (string)$this->input('health')),
        ]);
    }


    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'game_version_id' => Rule::in(GameVersion::ALL),
            'health'          => [
                'required',
                'regex:/^[\d\s,]*$/',
            ],
            'percentage'      => 'nullable|int',
        ];
    }
}
