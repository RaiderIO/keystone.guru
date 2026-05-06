<?php

namespace App\Http\Requests\MappingVersion;

use App\Models\GameVersion\GameVersion;
use App\Models\Mapping\MappingVersion;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class APIMappingVersionFormRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        // If minutes provided and seconds not, convert minutes -> seconds
        // If both provided, decide based on which differs from the current model value
        $minutes = $this->input('timer_max_minutes');
        $seconds = $this->input('timer_max_seconds');

        // Resolve MappingVersion from route model binding if present
        /** @var MappingVersion|null $mappingVersion */
        $mappingVersion = $this->route('mappingVersion');

        $currentSeconds = $mappingVersion?->timer_max_seconds;

        if ($minutes !== null && $seconds === null) {
            $this->merge([
                'timer_max_seconds' => (int)$minutes * 60,
            ]);
        } elseif ($minutes !== null && $seconds !== null) {
            // Both provided: pick the one that changed compared to current model
            if ($currentSeconds !== null) {
                $minutesAsSeconds = (int)$minutes * 60;
                $pickSeconds      = (int)$seconds !== $currentSeconds;
                $pickMinutes      = $minutesAsSeconds !== $currentSeconds;

                if ($pickSeconds && !$pickMinutes) {
                    $this->merge(['timer_max_seconds' => (int)$seconds]);
                } elseif ($pickMinutes && !$pickSeconds) {
                    $this->merge(['timer_max_seconds' => $minutesAsSeconds]);
                } else {
                    // If both changed (or neither known), prefer explicit seconds
                    $this->merge(['timer_max_seconds' => (int)$seconds]);
                }
            } else {
                // No current value available: prefer explicit seconds
                $this->merge(['timer_max_seconds' => (int)$seconds]);
            }
        }
    }

    protected function passedValidation(): void
    {
        // Ensure minutes field is not persisted
        $this->replace(collect($this->validated())
            ->except('timer_max_minutes')
            ->toArray());
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'game_version_id'                 => Rule::exists(GameVersion::class, 'id'),
            'enemy_forces_required'           => 'int',
            'enemy_forces_required_teeming'   => 'int|nullable',
            'enemy_forces_shrouded'           => 'int|nullable',
            'enemy_forces_shrouded_zul_gamux' => 'int|nullable',
            'timer_max_seconds'               => 'int|nullable',
            'timer_max_minutes'               => 'int|nullable',
            'facade_enabled'                  => 'nullable|boolean',
        ];
    }
}
