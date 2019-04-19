<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Role;
use App\User;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Laravel\Socialite\Facades\Socialite;

class LoginController extends Controller
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
     *
     * @var string
     */
    protected $redirectTo = '/';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest', ['except' => 'logout']);
    }

    /**
     * The user has been authenticated.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  mixed $user
     * @return mixed
     */
    protected function authenticated(Request $request, $user)
    {
        $this->redirectTo = $request->get('redirect', '/');
    }

    /**
     * Get the failed login response instance.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    protected function sendFailedLoginResponse(Request $request)
    {
        throw ValidationException::withMessages([
            $this->username() => [trans('auth.failed')],
        ])->redirectTo(route('login', ['redirect' => $request->get('redirect', '/')]));
    }

    /**
     * Redirect the user to the Google authentication page.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function redirectToProvider(Request $request)
    {
        $this->redirectTo = $request->get('redirect', '/');
        return Socialite::driver('google')->redirect();
    }

    /**
     * Obtain the user information from Google.
     *
     * @return \Illuminate\Http\Response
     */
    public function handleProviderCallback()
    {
        $googleUser = Socialite::driver('google')->user();

        /** @var User $existingUser */
        $existingUser = User::where('email', $googleUser->email)->first();
        // Does this user exist..
        if ($existingUser === null) {
            // Attach User role to any new user
            $userRole = Role::where('name', 'user')->first();

            // Create a new user
            $existingUser = User::create([
                // Prefer nickname over full name
                'name' => isset($googleUser->nickname) && $googleUser->nickname !== null ? $googleUser->nickname : $googleUser->name,
                'email' => $googleUser->email,
                'password' => '',
                'legal_agreed' => 1,
                'legal_agreed_ms' => -1
            ]);

            $existingUser->attachRole($userRole);
            \Session::flash('status', __('Registered successfully. Enjoy the website!'));
        }

        // Login either the new or the existing user
        Auth::login($existingUser, true);

        return redirect($this->redirectTo);
    }
}
