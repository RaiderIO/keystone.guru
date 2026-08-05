<?php

namespace App\Http\Requests\Api\V1\CombatLog\Run;

use App\Http\Requests\Api\V1\APIFormRequest;
use App\Models\Season;
use Illuminate\Validation\Rule;
use Override;

class CombatLogRunSegmentsRequest extends APIFormRequest
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
     * Both identifiers live in the path rather than in the payload, and validation only reads the payload -
     * merge them in so the `exists` rule below is what rejects an unknown season instead of a 500 further down.
     *
     * @return array<string, mixed>
     */
    #[Override]
    public function validationData(): array
    {
        return array_merge(parent::validationData(), [
            'season' => $this->route('season'),
            'runId'  => $this->route('runId'),
        ]);
    }

    /**
     * @return array<string, array<int, string|Rule>>
     */
    public function rules(): array
    {
        return [
            'season' => ['required', 'integer', Rule::exists(Season::class, 'id')],
            'runId'  => ['required', 'integer', 'min:1'],
        ];
    }

    public function season(): Season
    {
        return once(fn(): Season => Season::query()
            ->where('id', $this->validated('season'))
            ->firstOrFail());
    }

    public function runId(): int
    {
        return (int)$this->validated('runId');
    }
}
