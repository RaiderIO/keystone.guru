<?php

namespace App\Http\Controllers\Auth;

use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Psr\Log\LogLevel;
use RuntimeException;

class LoginController extends Controller implements HasMiddleware
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     */
    protected string $redirectTo = '/';

    public static function middleware(): array
    {
        return [
            new Middleware('guest', except: ['logout']),
        ];
    }

    protected function attemptLogin(Request $request): bool
    {
        try {
            return $this->guard()->attempt(
                $this->credentials($request),
                $request->boolean('remember'),
            );
        } catch (RuntimeException $exception) {
            // #2344 People trying to login with their OAuth account through normal methods (they don't have a password)
            if ($exception->getMessage() === 'This password does not use the Bcrypt algorithm.') {
                // Just tell them the credentials don't match, let them figure it out
                return false;
            } else {
                $all = $request->all();
                unset($all['password']);
                logger()->log(LogLevel::ERROR, $exception->getMessage(), $all);

                throw $exception;
            }
        }
    }

    /**
     * The user has been authenticated.
     */
    protected function authenticated(Request $request, mixed $user)
    {
        $this->redirectTo = $request->get('redirect', '/');
    }

    /**
     * Get the failed login response instance.
     *
     *
     * @throws ValidationException
     */
    protected function sendFailedLoginResponse(Request $request): never
    {
        throw ValidationException::withMessages([
            $this->username() => [trans('auth.failed')],
        ])->redirectTo(route('login', ['redirect' => $request->get('redirect', '/')]));
    }
}
