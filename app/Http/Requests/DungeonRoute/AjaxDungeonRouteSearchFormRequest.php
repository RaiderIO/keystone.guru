<?php

namespace App\Http\Requests\DungeonRoute;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AjaxDungeonRouteSearchFormRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Auth::user()->hasRole(["user", "admin"]);
    }

    /**
     * {@inheritDoc}
     */
    #[\Override]
    protected function failedValidation(Validator $validator)
    {
        $errors = new ValidationException($validator)->errors();

        throw new HttpResponseException(
            response()->json(['data' => $errors], 422),
        );
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $enemyPairRules = [
            'bail',
            'string',
            'regex:/^\d+;\d+$/',
            function (string $attribute, mixed $value, \Closure $fail) {
                [$npcId, $mdtId] = explode(';', $value);

                $exists = DB::table('enemies')
                    ->where('npc_id', $npcId)
                    ->where('mdt_id', $mdtId)
                    ->exists();

                if (!$exists) {
                    $fail("Enemy pair '{$value}' does not exist.");
                }
            },
        ];

        return [
            'offset'            => 'integer|nullable',
            'limit'             => 'integer|nullable|max:10',
            'minMythicLevel'    => 'integer|nullable',
            'maxMythicLevel'    => 'integer|nullable',
            'title'             => 'string|nullable',
            'username'          => 'string|nullable',
            'includedEnemies'   => 'array|nullable',
            'includedEnemies.*' => $enemyPairRules,
            'excludedEnemies'   => 'array|nullable',
            'excludedEnemies.*' => $enemyPairRules,
        ];
    }
}
