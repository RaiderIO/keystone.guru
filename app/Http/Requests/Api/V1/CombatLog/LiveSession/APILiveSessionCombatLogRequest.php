<?php

namespace App\Http\Requests\Api\V1\CombatLog\LiveSession;

use App\Http\Requests\Api\V1\APIFormRequest;
use App\Models\LiveSession\LiveSession;

class APILiveSessionCombatLogRequest extends APIFormRequest
{
    protected function getRequestDtoClass(): ?string
    {
        return null;
    }

    public function authorize(): bool
    {
        /** @var LiveSession $liveSession */
        $liveSession = $this->route('liveSession');

        return $this->user()?->can('ingest', $liveSession) ?? false;
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            'lines'          => ['required', 'array', 'min:1', sprintf('max:%d', config('keystoneguru.live_sessions.combat_log.max_lines_per_batch'))],
            'lines.*'        => ['required', 'string', sprintf('max:%d', config('keystoneguru.live_sessions.combat_log.max_line_length'))],
            'batch_sequence' => ['nullable', 'integer', 'min:0'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'lines.max'   => __('validation.custom.live_session_combat_log_lines.max'),
            'lines.*.max' => __('validation.custom.live_session_combat_log_line.max'),
        ];
    }
}
