<?php

namespace App\Http\Requests\Compendium;

use App\Models\Dungeon;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class NpcCompendiumRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function failedValidation(Validator $validator): never
    {
        throw new HttpResponseException(response()->json(['errors' => $validator->errors()], 422));
    }

    public function dungeon(): Dungeon
    {
        return once(fn() => Dungeon::findOrFail($this->validated('dungeon_id')));
    }

    /**


     * @return array<string, array<int, string|Rule>|string|Rule>
     */

    public function rules(): array
    {
        return [
            'dungeon_id' => ['required', 'integer', Rule::exists(Dungeon::class, 'id')],
        ];
    }
}
