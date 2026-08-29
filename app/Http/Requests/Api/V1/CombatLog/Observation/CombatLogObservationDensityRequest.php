<?php

namespace App\Http\Requests\Api\V1\CombatLog\Observation;

use App\Http\Requests\Api\V1\APIFormRequest;

class CombatLogObservationDensityRequest extends APIFormRequest
{
    protected function getRequestDtoClass(): ?string
    {
        return null;
    }

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'detailed' => ['nullable', 'boolean'],
        ];
    }

    /**
     * Whether the raw per-tuple list should be returned even when it exceeds the default cap (still bounded by
     * its own, larger cap).
     */
    public function isDetailed(): bool
    {
        return $this->boolean('detailed');
    }
}
