<?php

namespace App\Http\Requests\Heatmap;

use App\Models\Dungeon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GetDataFormRequest extends FormRequest
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
        return [
            'dungeon_id' => ['required', Rule::exists(Dungeon::class, 'id')],
        ];
    }
}

