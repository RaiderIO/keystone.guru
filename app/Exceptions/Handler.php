<?php

namespace App\Exceptions;

use App\Exceptions\Logging\HandlerLoggingInterface;
use Auth;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Teapot\StatusCode;
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
        BadRequestException::class,
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
        if (app()->has(HandlerLoggingInterface::class)) {
            $handlerLogging = app()->make(HandlerLoggingInterface::class);

            if ($e instanceof TooManyRequestsHttpException) {
                $user = Auth::user();
                $handlerLogging->tooManyRequests(request()?->ip() ?? 'unknown IP', request()?->path(), $user?->id, $user?->name, $e);
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
            } else if (!config('app.debug')) {
                return response()->json(['message' => __('exceptions.handler.internal_server_error')], StatusCode::INTERNAL_SERVER_ERROR);
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
}
