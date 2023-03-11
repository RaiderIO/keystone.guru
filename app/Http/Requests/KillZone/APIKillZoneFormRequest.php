<?php

namespace App\Http\Requests\KillZone;

use App\Models\Enemy;
use App\Models\Faction;
use App\Models\Floor;
use App\Models\Mapping\MappingVersion;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class APIKillZoneFormRequest extends FormRequest
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
            'id'        => 'nullable|int',
            'floor_id'  => ['nullable', Rule::exists(Floor::class, 'id')],
            'color'     => [
                'nullable',
                'string',
                'regex:/^#([a-f0-9]{6}|[a-f0-9]{3})$/i',
            ],
            'lat'       => 'nullable|numeric',
            'lng'       => 'nullable|numeric',
            'index'     => 'int',
            'enemies'   => 'array',
            'enemies.*' => 'int',
        ];
    }
}
