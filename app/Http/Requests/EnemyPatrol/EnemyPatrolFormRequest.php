<?php

namespace App\Http\Requests\EnemyPatrol;

use App\Models\Faction;
use App\Models\Polyline;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EnemyPatrolFormRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'id'                      => 'int',
            'floor_id'                => 'int',
            'polyline_id'             => ['nullable', Rule::exists(Polyline::class, 'id')],
            'teeming'                 => 'nullable|string',
            'faction'                 => [Rule::in(array_merge(array_keys(Faction::ALL), ['any']))],
            'polyline.color'          => 'string',
            'polyline.color_animated' => 'nullable|string',
            'polyline.weight'         => 'int',
            'polyline.vertices_json'  => 'string',
        ];
    }
}
