<?php

namespace App\Exceptions;

use App\Exceptions\Logging\HandlerLoggingInterface;
use App\Models\User;
use App\Service\Request\ApiRequestServiceInterface;
use Auth;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Validation\ValidationException;
use MarvinLabs\DiscordLogger\Discord\Exceptions\MessageCouldNotBeSent;
use Override;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Teapot\StatusCode;
use Teapot\StatusCode\RFC\RFC6585;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that should not be reported.
     *
     * @var array<int, class-string<Throwable>>
     */
    protected $dontReport = [
        AuthenticationException::class,
        AuthorizationException::class,
        HttpException::class,
        ModelNotFoundException::class,
        TokenMismatchException::class,
        ValidationException::class,
        // Added it to prevent spam from people trying to exploit the API
        // Now that I have better protection I want to see those exceptions again so I can ban their asses
        BadRequestException::class,
        MethodNotAllowedHttpException::class,
        NotFoundHttpException::class,
        AccessDeniedHttpException::class,
        // No point in logging Discord message send failures to Discord
        MessageCouldNotBeSent::class,
    ];

    /**
     * Report or log an exception.
     *
     * This is a great spot to send exceptions to Sentry, Bugsnag, etc.
     *
     * @return void
     *
     * @throws Throwable
     */
    #[Override]
    public function report(Throwable $e): void
    {
        // request() is not available in console
        $request = app()->runningInConsole() ? null : request();

        if (app()->has(HandlerLoggingInterface::class) && !app()->runningInConsole()) {
            $handlerLogging = app()->make(HandlerLoggingInterface::class);
            /** @var User|null $user */
            $user = Auth::user();

            if ($e instanceof TooManyRequestsHttpException) {
                $handlerLogging->tooManyRequests($request?->ip() ?? 'unknown IP', $request?->fullUrl(), $user?->id, $user?->name, $e);
            } elseif (!in_array($e::class, $this->dontReport)) {
                // parent::report() below also reports the exception itself whenever shouldReport() allows it, so this
                // record can be a duplicate - but only for that subset. $dontReport here is an exact class match while
                // shouldReport() matches with instanceof, so an HttpException subclass is logged here yet never
                // reported natively. Passing the answer along lets a sink drop the genuine duplicates without also
                // discarding the records that are the only trace of a failure.
                $handlerLogging->uncaughtException($request?->ip() ?? 'unknown IP', $request?->fullUrl(), $user?->id, $user?->name, $this->maskSensitiveVariables($request?->all()), $e::class, $e->getMessage(), $this->shouldReport($e));
            }
        }

        parent::report($e);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  Request $request
     * @return mixed
     *
     * @throws Throwable
     */
    #[Override]
    public function render($request, Throwable $e)
    {
        if ($this->shouldReturnJson($request, $e)) {
            if ($e instanceof ValidationException) {
                // ValidationException doesn't render an HTML error view (parent::render() either
                // returns JSON or redirects back with flashed session errors, per
                // shouldReturnJson() below), so it was never part of the #3806 crash class - leave
                // its normal expectsJson()-driven behavior alone rather than forcing JSON, since
                // some /ajax/ forms rely on the redirect+flash for a non-JSON request.
                return parent::render($request, $e);
            } elseif ($e instanceof ModelNotFoundException) {
                return response()->json([
                    'message' => __('exceptions.handler.api_model_not_found', [
                        'ids'   => implode(', ', $e->getIds()),
                        'model' => $e->getModel(),
                    ]),
                ], StatusCode::NOT_FOUND);
            } elseif ($e instanceof AuthenticationException) {
                // AuthenticationException is not an HttpExceptionInterface, so without this check it
                // falls through every instanceof branch below and hits the generic 500 tail instead
                // of the 401 unauthenticated() already builds - a guest hitting any auth-gated
                // /ajax/ or /api/ route would 500 instead of 401 (#3863).
                return $this->unauthenticated($request, $e);
            }

            // Normalize the same way parent::render() would (AuthorizationException -> 403,
            // TokenMismatchException -> 419, ...) before the instanceof checks below, so a
            // forced-JSON /ajax/ request (which isn't necessarily an HttpException already) gets
            // the exception's real status instead of collapsing into the generic 500 tail.
            $normalized = $this->prepareException($this->mapException($e));

            if ($normalized instanceof NotFoundHttpException) {
                return response()->json(['message' => __('exceptions.handler.api_route_not_found')], StatusCode::NOT_FOUND);
            } elseif ($normalized instanceof ThrottleRequestsException) {
                return response()->json(['message' => __('exceptions.handler.too_many_requests')], RFC6585::TOO_MANY_REQUESTS);
            } elseif ($normalized instanceof MethodNotAllowedHttpException) {
                // The router's own message here is a verbose "The GET method is not supported for
                // route ...", not meant for API consumers - use the plain status text instead, but
                // still preserve the Allow header.
                $statusCode = $normalized->getStatusCode();

                return response()->json(
                    ['message' => SymfonyResponse::$statusTexts[$statusCode] ?? __('exceptions.handler.internal_server_error')],
                    $statusCode,
                    $normalized->getHeaders(),
                );
            } elseif ($normalized instanceof HttpExceptionInterface) {
                // Any other HttpException (e.g. a policy denial normalized above, or a deliberate
                // abort($status, $message) elsewhere in the app) carries a message meant to be shown
                // to the caller - delegate to parent::render() so it's preserved verbatim instead of
                // being collapsed into a generic status text.
                return parent::render($request, $e);
            } elseif (!config('app.debug')) {
                return response()->json(['message' => __('exceptions.handler.internal_server_error')], StatusCode::INTERNAL_SERVER_ERROR);
            } elseif (config('app.type') !== 'local') {
                return response()->json(['message' => $e->getMessage()], StatusCode::INTERNAL_SERVER_ERROR);
            }
        }

        return parent::render($request, $e);
    }

    /**
     * Convert an authentication exception into an unauthenticated response.
     *
     * @param  Request $request
     * @return mixed
     */
    #[Override]
    protected function unauthenticated($request, AuthenticationException $exception)
    {
        if ($this->shouldReturnJson($request, $exception)) {
            // defaultAjaxErrorFn() (resources/assets/js/custom/inline/layouts/app.js) only reads
            // responseJSON.errors then responseJSON.message - 'error' was silently dropped, falling
            // back to the generic "An error occurred" toast.
            return response()->json(['message' => __('exceptions.handler.unauthenticated')], StatusCode::UNAUTHORIZED);
        }

        return redirect()->guest('login');
    }

    /**
     * An API request must never be answered with an HTML error view: no view composer runs for it
     * (KeystoneGuruServiceProvider::boot() skips registering them for any path
     * ViewService::shouldLoadViewVariables() rejects, which is every API path bar a few that render
     * a view on purpose), so such a view is guaranteed to crash on a missing view variable
     * (#3806, #3903). Force JSON for those - except ValidationException, which render() above
     * already carves out since it never renders an HTML error view in the first place.
     *
     * Whether the request is an API request is ApiRequestService's call, not something derived from
     * the view layer: it is resolved here rather than constructor-injected to match how this class
     * already resolves HandlerLoggingInterface, keeping the exception handler constructible even
     * when the container is in a degraded state. The previous hand-rolled check
     * (str_starts_with($path, 'api/') || str_starts_with($path, 'ajax/')) that the service replaces
     * had drifted in two ways: it never covered '/benchmark' at all, and Request::decodedPath()
     * trims the trailing slash, so a bare "/api/" request decoded to "api" and silently failed
     * "starts with 'api/'" (#3903).
     */
    #[Override]
    protected function shouldReturnJson($request, Throwable $e): bool
    {
        if ($request->isJson()) {
            return true;
        }

        if ($e instanceof ValidationException) {
            return parent::shouldReturnJson($request, $e);
        }

        return app(ApiRequestServiceInterface::class)->isApiRequest($request)
            || parent::shouldReturnJson($request, $e);
    }

    /**
     * @param  array<string, mixed>|null $array
     * @return array<string, mixed>|null
     */
    private function maskSensitiveVariables(?array $array): ?array
    {
        if ($array === null) {
            return null;
        }

        $keys = [
            '_token',
            'password',
            'password_confirmation',
        ];
        foreach ($keys as $key) {
            if (isset($array[$key]) && is_string($array[$key])) {
                $array[$key] = '*********';
            }
        }

        return $array;
    }
}
