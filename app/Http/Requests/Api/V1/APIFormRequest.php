<?php

namespace App\Http\Requests\Api\V1;

use App\Dto\Request\RequestDTO;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Override;

abstract class APIFormRequest extends FormRequest
{
    protected abstract function getRequestDTOClass(): ?string;

    #[Override]
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => 'Validation errors',
            'data'    => $validator->errors(),
        ], 422));
    }

    public function getDTO(): ?RequestDTO
    {
        $requestDTOClass = $this->getRequestDTOClass();
        if ($requestDTOClass === null) {
            return null;
        }

        return new $requestDTOClass($this->validated());
    }
}
