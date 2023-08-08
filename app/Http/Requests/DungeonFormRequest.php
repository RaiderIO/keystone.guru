<?php

namespace App\Http\Requests;

use App\Models\Dungeon;
use Auth;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DungeonFormRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return Auth::user()->hasRole('admin');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'active'           => 'nullable|boolean',
            'speedrun_enabled' => 'nullable|boolean',
            'zone_id'          => 'int',
            'map_id'           => 'int',
            'mdt_id'           => 'int',
            'name'             => ['required', Rule::unique('dungeons', 'name')->ignore($this->get('name'), 'name')],
            'key'              => [
                'required',
                Rule::unique('dungeons', 'key')->ignore($this->get('key'), 'key'),
                Rule::in(collect(Dungeon::ALL)->flatten()),
            ],
            'slug'             => ['required', Rule::unique('dungeons', 'slug')->ignore($this->get('slug'), 'slug')],
        ];
    }
}
