<?php

namespace App\Http\Requests\Api\V1;

class APIOffsetPaginatedRequest extends APIFormRequest
{
    protected function getRequestModelClass(): ?string
    {
        return null;
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'offset' => ['nullable', 'integer', 'min:0'],
            'count'  => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    public function getOffset(): int
    {
        return $this->validated('offset') ?? 0;
    }

    public function getCount(): int
    {
        return $this->validated('count') ?? 10;
    }
}
