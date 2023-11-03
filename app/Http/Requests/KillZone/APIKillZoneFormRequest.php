<?php

namespace App\Http\Requests\KillZone;

use App\Models\Floor\Floor;
use App\Models\Spell;
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
            'id'          => 'nullable|int',
            'floor_id'    => ['nullable', Rule::exists(Floor::class, 'id')],
            'color'       => [
                'nullable',
                'string',
                'regex:/^#([a-f0-9]{6}|[a-f0-9]{3})$/i',
            ],
            'description' => 'nullable|string|max:255',
            'lat'         => 'nullable|numeric',
            'lng'         => 'nullable|numeric',
            'index'       => 'int',
            'enemies'     => 'array',
            // Do not validate here - it's slow and we validate ourselves against the accurate mapping version
            'enemies.*'   => 'int',
            'spells'      => 'array',
            'spells.*'    => Rule::exists(Spell::class, 'id'),
        ];
    }
}
