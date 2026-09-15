<?php

namespace App\Http\Requests\KillZone;

use Illuminate\Foundation\Http\FormRequest;

class APIKillZoneMassFormRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, string>|string> */
    public function rules(): array
    {
        return [
            'killzones' => sprintf('nullable|array|max:%d', config('keystoneguru.dungeon_route_limits.kill_zones')),
            // A kill zone the client names by id must exist: without this the row is created under a
            // database-assigned id instead, and the client - which never re-reads the ids from this
            // endpoint's response - keeps rendering the pull under the id it submitted
            'killzones.*.id'    => 'nullable|integer|exists:kill_zones,id',
            'killzones.*.color' => [
                'required',
                'regex:/^#([a-f0-9]{6}|[a-f0-9]{3})$/i',
            ],
            'killzones.*.index'     => 'int',
            'killzones.*.enemies'   => 'nullable|array',
            'killzones.*.enemies.*' => 'integer',
        ];
    }
}
