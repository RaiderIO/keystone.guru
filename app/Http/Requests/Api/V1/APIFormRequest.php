<?php

namespace App\Http\Requests\Api\V1;

use App\Dto\Request\RequestDto;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Override;
use Teapot\StatusCode;

abstract class APIFormRequest extends FormRequest
{
    protected abstract function getRequestDtoClass(): ?string;

    #[Override]
    protected function failedAuthorization(): never
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => __('exceptions.handler.forbidden'),
        ], StatusCode::FORBIDDEN));
    }

    #[Override]
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => 'Validation errors',
            'data'    => $validator->errors(),
        ], 422));
    }

    public function getDto(): ?RequestDto
    {
        $requestDtoClass = $this->getRequestDtoClass();
        if ($requestDtoClass === null) {
            return null;
        }

        return new $requestDtoClass($this->validated());
    }
}
