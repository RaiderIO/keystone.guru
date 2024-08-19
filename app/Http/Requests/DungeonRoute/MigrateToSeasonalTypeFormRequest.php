<?php

namespace App\Http\Requests\DungeonRoute;

use App\Models\Affix;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MigrateToSeasonalTypeFormRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Auth::user()->hasRole(["user", "admin"]);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'seasonal_type' => Rule::in(Affix::SEASONAL_AFFIXES),
        ];
    }
}
