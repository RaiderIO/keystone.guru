<?php

namespace App\Http\Requests\DungeonRoute;

use App\Models\Affix;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MigrateToSeasonalTypeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true; // Auth::user()->hasRole(["user", "admin"]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'seasonal_type' => Rule::in(Affix::SEASONAL_AFFIXES),
        ];
    }
}
