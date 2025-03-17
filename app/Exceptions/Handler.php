<?php

namespace App\Exceptions;

use App\Exceptions\Logging\HandlerLoggingInterface;
use Auth;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
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
     * @var array
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
    public function report(Throwable $e): void
    {
        // request() is not available in console
        $request = app()->runningInConsole() ? null : request();

        if (app()->has(HandlerLoggingInterface::class) && !app()->runningInConsole()) {
            $handlerLogging = app()->make(HandlerLoggingInterface::class);
            $user           = Auth::user();

            if ($e instanceof TooManyRequestsHttpException) {
                $handlerLogging->tooManyRequests($request?->ip() ?? 'unknown IP', $request?->fullUrl(), $user?->id, $user?->name, $e);
            } else if (!in_array(get_class($e), $this->dontReport)) {
                $handlerLogging->uncaughtException($request?->ip() ?? 'unknown IP', $request?->fullUrl(), $user?->id, $user?->name, $this->maskSensitiveVariables($request?->all()), get_class($e), $e->getMessage());
            }
        }

        parent::report($e);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param Request $request
     * @return mixed
     *
     * @throws Throwable
     */
    public function render($request, Throwable $e)
    {
        if ($request->isJson() || $this->isApiRequest($request)) {
            if ($e instanceof ModelNotFoundException) {
                return response()->json([
                    'message' => __('exceptions.handler.api_model_not_found', [
                        'ids'   => implode(', ', $e->getIds()),
                        'model' => $e->getModel(),
                    ]),
                ], StatusCode::NOT_FOUND);
            } else if ($e instanceof NotFoundHttpException) {
                return response()->json(['message' => __('exceptions.handler.api_route_not_found')], StatusCode::NOT_FOUND);
            } else if ($e instanceof ThrottleRequestsException) {
                return response()->json(['message' => __('exceptions.handler.too_many_requests')], RFC6585::TOO_MANY_REQUESTS);
            } else if (!config('app.debug')) {
                return response()->json(['message' => __('exceptions.handler.internal_server_error')], StatusCode::INTERNAL_SERVER_ERROR);
            } else if (config('app.type') !== 'local') {
                return response()->json(['message' => $e->getMessage()], StatusCode::INTERNAL_SERVER_ERROR);
            }
        }

        return parent::render($request, $e);
    }

    /**
     * Convert an authentication exception into an unauthenticated response.
     *
     * @param Request $request
     * @return mixed
     */
    protected function unauthenticated($request, AuthenticationException $exception)
    {
        if ($request->isJson() || $this->isApiRequest($request)) {
            return response()->json(['error' => __('exceptions.handler.unauthenticated')], StatusCode::UNAUTHORIZED);
        }

        return redirect()->guest('login');
    }

    private function isApiRequest(Request $request): bool
    {
        return str_starts_with($request->decodedPath(), 'api/');
    }

    private function maskSensitiveVariables(?array $array): ?array
    {
        if ($array === null) {
            return null;
        }

        $keys = ['_token', 'password', 'password_confirmation'];
        foreach ($keys as $key) {
            if (isset($array[$key]) && is_string($array[$key])) {
                $array[$key] = '*********';
            }
        }

        return $array;
    }
}
