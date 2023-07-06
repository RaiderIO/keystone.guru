<?php

namespace App\Http\Requests\DungeonRoute;

use App\Models\Expansion;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class APIDungeonRouteSearchFormRequest extends FormRequest
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
     * @inheritDoc
     */
    protected function failedValidation(Validator $validator)
    {
        $errors = (new ValidationException($validator))->errors();
        throw new HttpResponseException(
            response()->json(['data' => $errors], 422)
        );
    }


    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'offset'    => 'integer|required',
            'limit'     => 'integer|required',
            'title'     => 'string',
            'expansion' => [Rule::in(
                Expansion::active()
                    ->get()
                    ->pluck('shortname')
                    ->toArray()
            )],
        ];
    }
}
