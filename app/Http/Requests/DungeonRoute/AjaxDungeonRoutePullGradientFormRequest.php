<?php

namespace App\Http\Requests\DungeonRoute;

use App\Models\DungeonRoute\DungeonRoute;
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
     * One handler in any notation a client may send: surrounding whitespace, a signed or exponent-notation
     * position (a JS float such as `2.220446049250313e-16`), an optional `%`, and a hex colour.
     */
    private const string LENIENT_HANDLER_REGEX = '/^\s*([+-]?(?:\d+(?:\.\d*)?|\.\d+)(?:e[+-]?\d+)?)(%?)\s+(#(?:[a-f0-9]{3}|[a-f0-9]{6}|[a-f0-9]{8}))\s*$/i';

    private const string PLAIN_POSITION_REGEX = '/^\d{1,3}(\.\d+)?$/';

    private const int POSITION_DECIMALS = 4;

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

    /**
     * Rewrites the gradient into the canonical `<position>[%] <hex>` form before it is validated. A gradient
     * with a handler that cannot be read is left as-is so the format rule rejects it - unless it is the
     * gradient already stored on the route, which the editor resends verbatim on every settings change;
     * those unreadable handlers are dropped instead, as the editor itself does when it loads them.
     */
    protected function prepareForValidation(): void
    {
        $pullGradient = $this->input('pull_gradient');
        if (!is_string($pullGradient) || trim($pullGradient) === '') {
            return;
        }

        $dungeonRoute         = $this->route('dungeonRoute');
        $isStoredPullGradient = $dungeonRoute instanceof DungeonRoute && $dungeonRoute->pull_gradient === $pullGradient;

        $handlers = [];
        foreach (explode(',', $pullGradient) as $handler) {
            if (preg_match(self::LENIENT_HANDLER_REGEX, $handler, $matches) !== 1) {
                if ($isStoredPullGradient) {
                    continue;
                }

                return;
            }

            $handlers[] = sprintf('%s%s %s', $this->normalizePosition($matches[1]), $matches[2], $matches[3]);
        }

        $this->merge(['pull_gradient' => $handlers === [] ? null : implode(',', $handlers)]);
    }

    /**
     * Keeps a plain decimal position verbatim; any other notation becomes a plain decimal clamped to 0-100.
     */
    private function normalizePosition(string $position): string
    {
        if (preg_match(self::PLAIN_POSITION_REGEX, $position) === 1) {
            return $position;
        }

        $clamped   = max(0.0, min(100.0, (float)$position));
        $formatted = number_format($clamped, self::POSITION_DECIMALS, '.', '');

        return rtrim(rtrim($formatted, '0'), '.');
    }
}
