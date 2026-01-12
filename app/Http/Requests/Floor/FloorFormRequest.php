<?php

namespace App\Http\Requests\Floor;

use App\Models\Laratrust\Role;
use Auth;
use Illuminate\Foundation\Http\FormRequest;

class FloorFormRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Auth::user()->hasRole(Role::ROLE_ADMIN);
    }
    /**
     * Determine if the user is authorized to make this request.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'active'  => $this->input('active', 0),
            'default' => $this->input('default', 0),
            'facade'  => $this->input('facade', 0),
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'active' => [
                'nullable',
                'bool',
            ],
            'default' => [
                'nullable',
                'bool',
            ],
            'facade' => [
                'nullable',
                'bool',
            ],
            'map_name' => [
                'nullable',
                'string',
            ],
            'name' => [
                'required',
                'string',
            ],
            'index' => [
                'required',
                'integer',
            ],
            'mdt_sub_level' => [
                'nullable',
                'integer',
            ],
            'ui_map_id' => [
                'required',
                'integer',
            ],
            'min_enemy_size' => [
                'nullable',
                'integer',
            ],
            'max_enemy_size' => [
                'nullable',
                'integer',
            ],
            'enemy_engagement_max_range' => [
                'nullable',
                'integer',
            ],
            'enemy_engagement_max_range_patrols' => [
                'nullable',
                'integer',
            ],
            'percentage_display_zoom' => [
                'nullable',
                'integer',
            ],
            'zoom_max' => [
                'nullable',
                'integer',
            ],
        ];
    }
}
