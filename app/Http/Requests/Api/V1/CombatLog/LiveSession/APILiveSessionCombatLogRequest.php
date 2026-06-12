<?php

namespace App\Http\Requests\Api\V1\CombatLog\LiveSession;

use App\Http\Requests\Api\V1\APIFormRequest;
use App\Models\LiveSession;

class APILiveSessionCombatLogRequest extends APIFormRequest
{
    protected function getRequestModelClass(): ?string
    {
        return null;
    }

    public function authorize(): bool
    {
        /** @var LiveSession $liveSession */
        $liveSession = $this->route('liveSession');

        return $this->user()?->can('view', $liveSession) ?? false;
    }

    public function rules(): array
    {
        return [
            'lines'          => ['required', 'array', 'min:1'],
            'lines.*'        => ['required', 'string'],
            'batch_sequence' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
