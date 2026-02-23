<?php

namespace App\Http\Requests\DungeonRoute;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
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
        return [
            'offset'   => 'integer|nullable',
            'limit'    => 'integer|nullable|max:10',
            'title'    => 'string|nullable',
            'username' => 'string|nullable',
        ];
    }
}
