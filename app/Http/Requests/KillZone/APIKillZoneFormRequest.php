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
    }    /**
     * @return array<string, array<int, string|Rule>|string|Rule>
     */
    public function rules(): array
    {
        return [
            'id'       => 'nullable|int',
            'floor_id' => [
                'nullable',
                Rule::exists(Floor::class, 'id'),
            ],
            'color' => [
                'nullable',
                'string',
                'regex:/^#([a-f0-9]{6}|[a-f0-9]{3})$/i',
            ],
            'description' => sprintf('nullable|string|max:%d', KillZone::DESCRIPTION_MAX_LENGTH),
            'lat'         => 'nullable|numeric',
            'lng'         => 'nullable|numeric',
            'index'       => 'int',
            'enemies'     => 'array',
            // Do not validate here - it's slow and we validate ourselves against the accurate mapping version
            'enemies.*' => 'int',
            'spells'    => 'array',
            'spells.*'  => Rule::exists(Spell::class, 'id'),
        ];
    }
}
