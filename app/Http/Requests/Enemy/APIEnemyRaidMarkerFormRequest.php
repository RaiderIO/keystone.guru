<?php

namespace App\Http\Requests\Enemy;

use Illuminate\Foundation\Http\FormRequest;

class APIEnemyRaidMarkerFormRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
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
            // Present but empty clears the marker; a request without it must not touch any marker
            'raid_marker_key' => [
                'present',
                'nullable',
                'string',
            ],
        ];
    }
}
