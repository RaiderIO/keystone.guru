<?php namespace App\Http\Middleware;

use App\Service\User\UserServiceInterface;
use Closure;
use Illuminate\Http\Request;
use Teapot\StatusCode;

class ApiAuthentication
{
    private UserServiceInterface $userService;

    /**
     * @param UserServiceInterface $userService
     */
    public function __construct(UserServiceInterface $userService)
    {
        $this->userService = $userService;
    }

    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     *
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        if (!$this->userService->longAsUserFromAuthenticationHeader($request)) {
            return response('Unauthorized.', StatusCode::UNAUTHORIZED);
        }

        return $next($request);
    }
}
