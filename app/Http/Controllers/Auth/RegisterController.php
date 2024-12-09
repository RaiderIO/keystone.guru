<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Middleware\TrustProxies;
use App\Models\GameServerRegion;
use App\Models\Laratrust\Role;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Random\RandomException;
use Session;

class RegisterController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Register Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles the registration of new users as well as their
    | validation and creation. By default this controller uses a trait to
    | provide this functionality without requiring any additional code.
    |
    */

    use RegistersUsers;

    /**
     * Where to redirect users after registration.
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
        $this->middleware(['guest', TrustProxies::class, 'throttle:create-user']);
    }

    /**
     * Get a validator for an incoming registration request.
     */
    protected function validator(array $data): \Illuminate\Contracts\Validation\Validator
    {
        return Validator::make($data, [
            'name'                  => 'required|alpha_dash|max:32|unique:users',
            'email'                 => 'required|email|max:255|unique:users',
            'game_server_region_id' => 'nullable|int',
            'password'              => 'required|min:8|confirmed',
            'legal_agreed'          => 'required|accepted',
        ], [
            'legal_agreed.required' => __('controller.register.legal_agreed_required'),
            'legal_agreed.accepted' => __('controller.register.legal_agreed_accepted'),
        ]);
    }

    /**
     * Create a new user instance after a valid registration.
     * @throws RandomException
     */
    protected function create(array $data): User
    {
        // Attach User role to any new user
        $userRole = Role::firstWhere('name', Role::ROLE_USER);

        /** @var User $user */
        $user = User::create([
            'public_key'            => User::generateRandomPublicKey(),
            'name'                  => $data['name'],
            'email'                 => $data['email'],
            'echo_color'            => randomHexColor(),
            'game_server_region_id' => $data['region'] ?? GameServerRegion::DEFAULT_REGION,
            'password'              => Hash::make($data['password']),
            'legal_agreed'          => $data['legal_agreed'],
            'legal_agreed_ms'       => intval($data['legal_agreed_ms']),
        ]);

        $user->addRole($userRole);

        return $user;
    }

    /**
     * Handle a registration request for the application.
     *
     * @return Application|RedirectResponse|Response|Redirector
     *
     * @throws ValidationException
     */
    public function register(Request $request)
    {
        /** @var \Illuminate\Validation\Validator $validator */
        $validator = $this->validator($request->all());
        try {
            $validator->validate();
        } catch (ValidationException) {
            // We always want to redirect to /register, even if you tried to register from modal anywhere on the side
            return redirect('/register')->withInput()->withErrors($validator->messages()->getMessages());
        }

        event(new Registered($user = $this->create($request->all())));

        $this->guard()->login($user);

        Session::flash('status', __('controller.register.flash.registered_successfully'));

        // Set the redirect path if it was set
        $this->redirectTo = $request->get('redirect', '/profile');

        return $this->registered($request, $user) ?: redirect($this->redirectPath());
    }
}
