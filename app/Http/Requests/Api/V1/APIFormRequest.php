<?php

namespace App\Http\Requests\Api\V1;

use App\Http\Models\Request\RequestModel;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

abstract class APIFormRequest extends FormRequest
{
    protected abstract function getRequestModelClass(): ?string;

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => 'Validation errors',
            'data'    => $validator->errors(),
        ]));
    }

    public function getModel(): ?RequestModel
    {
        $requestModelClass = $this->getRequestModelClass();
        if ($requestModelClass === null) {
            return null;
        }

        return new $requestModelClass($this->validated());
    }
}
