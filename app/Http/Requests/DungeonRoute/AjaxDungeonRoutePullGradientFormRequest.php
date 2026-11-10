<?php

namespace App\Http\Requests\DungeonRoute;

use Illuminate\Foundation\Http\FormRequest;

class AjaxDungeonRoutePullGradientFormRequest extends FormRequest
{
    /**
     * Comma-separated `<position> <hex colour>` handlers, as the pull gradient editor builds them, e.g.
     * `0 #ff0000,47.5 #00ff00,100 #0000ffcc`.
     */
    public const string PULL_GRADIENT_REGEX = '/^(\d{1,3}(\.\d+)?%? #([a-f0-9]{3}|[a-f0-9]{6}|[a-f0-9]{8}))(,\d{1,3}(\.\d+)?%? #([a-f0-9]{3}|[a-f0-9]{6}|[a-f0-9]{8}))*$/i';

    public const int PULL_GRADIENT_MAX_LENGTH = 2000;

    /**
     * Authorization happens in the controller against the route being edited.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>|string>
     */
    public function rules(): array
    {
        return [
            'pull_gradient' => [
                'nullable',
                'string',
                sprintf('max:%d', self::PULL_GRADIENT_MAX_LENGTH),
                sprintf('regex:%s', self::PULL_GRADIENT_REGEX),
            ],
            'pull_gradient_apply_always' => ['nullable', 'boolean'],
        ];
    }
}
