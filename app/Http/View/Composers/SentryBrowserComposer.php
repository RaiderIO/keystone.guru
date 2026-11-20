<?php

namespace App\Http\View\Composers;

use App\Logging\SensitiveDataRedactor;
use App\Models\User;
use App\Service\View\ViewServiceInterface;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

readonly class SentryBrowserComposer implements ViewComposerInterface
{
    public const string SDK_URL = 'https://browser.sentry-cdn.com/11.16.0/bundle.min.js';

    public const string SDK_INTEGRITY = 'sha384-qHZbZKUz6pLGlAQv3oRy9uoyDgX96BbA+B08/ka9u8NxljupBw8wz1bAvr4C3z77';

    /**
     * Messages browsers raise for code that is not ours or that carries no actionable information.
     */
    private const array IGNORED_ERRORS = [
        'Script error.',
        'Script error',
        'ResizeObserver loop limit exceeded',
        'ResizeObserver loop completed with undelivered notifications.',
    ];

    public function __construct(
        private ViewServiceInterface $viewService,
    ) {
    }

    public function compose(View $view): void
    {
        $dsn            = config('keystoneguru.sentry.browser_dsn');
        $appVersionInfo = $this->viewService->getAppVersionInfo();

        $view->with('sentryBrowserConfig', empty($dsn) ? null : $this->buildConfig($dsn));
        $view->with('sentryBrowserScrubFields', SensitiveDataRedactor::browserScrubFields());
        $view->with('sentryBrowserBundleRevision', $appVersionInfo['revision']);
        $view->with('sentryBrowserOwnOrigins', $this->getOwnOrigins());
    }

    /**
     * @return array{
     *     dsn: string,
     *     environment: string|null,
     *     release: string|null,
     *     sampleRate: float,
     *     sendDefaultPii: false,
     *     ignoreErrors: array<int, string>,
     *     initialScope: array{user?: array{id: string, username: string}},
     * }
     */
    private function buildConfig(string $dsn): array
    {
        $initialScope = [];

        /** @var User|null $user */
        $user = Auth::user();
        if ($user !== null) {
            $initialScope['user'] = [
                'id'       => (string)$user->id,
                'username' => $user->name,
            ];
        }

        return [
            'dsn'            => $dsn,
            'environment'    => config('sentry.environment'),
            'release'        => config('sentry.release'),
            'sampleRate'     => (float)config('keystoneguru.sentry.browser_sample_rate'),
            'sendDefaultPii' => false,
            'ignoreErrors'   => self::IGNORED_ERRORS,
            'initialScope'   => $initialScope,
        ];
    }

    /**
     * The origin the compiled bundles are served from when it differs from the page's; the page's own origin is
     * added in the browser.
     *
     * @return array<int, string>
     */
    private function getOwnOrigins(): array
    {
        $assetsBaseUrl = parse_url((string)config('keystoneguru.assets_base_url'));
        if (!is_array($assetsBaseUrl) || !isset($assetsBaseUrl['scheme'], $assetsBaseUrl['host'])) {
            return [];
        }

        return [
            sprintf(
                '%s://%s%s',
                $assetsBaseUrl['scheme'],
                $assetsBaseUrl['host'],
                isset($assetsBaseUrl['port']) ? sprintf(':%d', $assetsBaseUrl['port']) : '',
            ),
        ];
    }
}
