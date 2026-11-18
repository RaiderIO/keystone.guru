<?php

namespace App\Http\View\Composers;

use App\Logging\SensitiveDataRedactor;
use App\Models\User;
use App\Service\View\ViewServiceInterface;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

readonly class RollbarComposer implements ViewComposerInterface
{
    /**
     * Per page load. Rollbar's own project-level rate limit is the backstop across visitors.
     */
    private const int MAX_ITEMS = 50;

    private const int ITEMS_PER_MINUTE = 30;

    /**
     * Messages browsers raise for code that is not ours or that carries no actionable information.
     */
    private const array IGNORED_MESSAGES = [
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
        $accessToken    = config('keystoneguru.rollbar.client_access_token');
        $appVersionInfo = $this->viewService->getAppVersionInfo();

        $view->with('rollbarConfig', empty($accessToken) ? null : $this->buildConfig($accessToken, $appVersionInfo['version']));
        $view->with('rollbarBundleRevision', $appVersionInfo['revision']);
        $view->with('rollbarOwnOrigins', $this->getOwnOrigins());
    }

    /**
     * @return array<string, mixed>
     */
    private function buildConfig(string $accessToken, string $codeVersion): array
    {
        $payload = [
            'environment' => config('app.env'),
            'client'      => [
                'javascript' => [
                    'code_version' => $codeVersion,
                ],
            ],
        ];

        /** @var User|null $user */
        $user = Auth::user();
        if ($user !== null) {
            $payload['person'] = [
                'id'       => (string)$user->id,
                'username' => $user->name,
            ];
        }

        return [
            'accessToken'                => $accessToken,
            'captureUncaught'            => true,
            'captureUnhandledRejections' => true,
            'maxItems'                   => self::MAX_ITEMS,
            'itemsPerMinute'             => self::ITEMS_PER_MINUTE,
            'scrubFields'                => SensitiveDataRedactor::browserScrubFields(),
            'ignoredMessages'            => self::IGNORED_MESSAGES,
            'payload'                    => $payload,
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
