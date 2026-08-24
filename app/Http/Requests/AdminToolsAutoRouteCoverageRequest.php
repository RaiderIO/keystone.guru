<?php

namespace App\Http\Requests;

use App\Models\Laratrust\Role;
use Auth;
use Illuminate\Foundation\Http\FormRequest;

class AdminToolsAutoRouteCoverageRequest extends FormRequest
{
    private const DEFAULT_DAYS = 7;

    public function authorize(): bool
    {
        return Auth::user()->hasRole(Role::ROLE_ADMIN);
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'days' => ['nullable', 'integer', 'min:1', 'max:365'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'days.integer' => __('validation.custom.autoroutecoverage_days.integer'),
            'days.min'     => __('validation.custom.autoroutecoverage_days.min'),
            'days.max'     => __('validation.custom.autoroutecoverage_days.max'),
        ];
    }

    /**
     * The amount of days of Auto Route Creator output the overview should cover.
     */
    public function getDays(): int
    {
        $days = $this->validated('days');

        return $days === null ? self::DEFAULT_DAYS : (int)$days;
    }
}
