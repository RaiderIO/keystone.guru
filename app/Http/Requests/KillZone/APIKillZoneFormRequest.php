<?php

namespace App\Http\Requests\KillZone;

use App\Http\Requests\Traits\CastInputData;
use App\Models\Floor\Floor;
use App\Models\KillZone\KillZone;
use App\Models\Spell\Spell;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class APIKillZoneFormRequest extends FormRequest
{
    use CastInputData;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge($this->castInputData($this, KillZone::class));
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'id'          => 'nullable|int',
            'floor_id' => [
                'nullable',
                Rule::exists(Floor::class, 'id'),
            ],
            'color'       => [
                'nullable',
                'string',
                'regex:/^#([a-f0-9]{6}|[a-f0-9]{3})$/i',
            ],
            'description' => 'nullable|string|max:500',
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
