<?php

namespace App\Http\Requests;

class AdminToolsTelemetryDataRequest extends AdminToolsTelemetryRequest
{
    /**
     * @return array<string, array<int, string>>
     */
    #[\Override]
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'measurement' => ['required', 'string', 'max:32'],
            'name'        => ['nullable', 'string', 'max:64'],
        ]);
    }

    public function getMeasurement(): string
    {
        return (string)$this->validated('measurement');
    }

    /**
     * The single name to return, or null for every name within the measurement - a gauge like `redis` is one
     * chart holding every one of its names, while each scheduled command gets a chart of its own.
     */
    public function getName(): ?string
    {
        $name = $this->validated('name');

        return $name === null ? null : (string)$name;
    }
}
