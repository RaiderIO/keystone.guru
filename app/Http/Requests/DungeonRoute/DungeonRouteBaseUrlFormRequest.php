<?php

namespace App\Http\Requests\DungeonRoute;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DungeonRouteBaseUrlFormRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $validLocales = [];
        foreach (config('language.all') as $language) {
            $validLocales[] = $language['short'];
            $validLocales[] = $language['long'];
        }

        return [
            'lat'    => 'numeric',
            'lng'    => 'numeric',
            'z'      => 'numeric',
            'locale' => [
                'nullable',
                Rule::in($validLocales),
            ],
        ];
    }
}
