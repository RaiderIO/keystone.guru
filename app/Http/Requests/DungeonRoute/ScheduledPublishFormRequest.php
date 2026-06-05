<?php

namespace App\Http\Requests\DungeonRoute;

use App\Models\DungeonRoute\DungeonRouteScheduledPublish;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ScheduledPublishFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'published_state' => [
                'required',
                'string',
                Rule::in(DungeonRouteScheduledPublish::SCHEDULABLE_PUBLISH_STATES),
            ],
            'publish_at' => ['required', 'date', 'after:now'],
        ];
    }
}
