<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Role;
use App\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
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
        $this->middleware('guest');
    }

    /**
     * Get a validator for an incoming registration request.
     *
     * @param array $data
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function validator(array $data)
    {
        return Validator::make($data, [
            'name'                  => 'required|alpha_dash|max:32|unique:users',
            'email'                 => 'required|email|max:255|unique:users',
            'game_server_region_id' => 'nullable|int',
            'password'              => 'required|min:8|confirmed',
            'legal_agreed'          => 'required|accepted'
        ], [
            'legal_agreed.required' => __('You have to agree to our legal terms to register.'),
            'legal_agreed.accepted' => __('You have to agree to our legal terms to register. 2')
        ]);
    }

    /**
     * Create a new user instance after a valid registration.
     *
     * @param array $data
     * @return User
     */
    protected function create(array $data)
    {
        // Attach User role to any new user
        $userRole = Role::where('name', 'user')->first();

        /** @var User $user */
        $user = User::create([
            'name'                  => $data['name'],
            'email'                 => $data['email'],
            'echo_color'            => randomHexColor(),
            'game_server_region_id' => $data['region'],
            'password'              => bcrypt($data['password']),
            'legal_agreed'          => $data['legal_agreed'],
            'legal_agreed_ms'       => intval($data['legal_agreed_ms'])
        ]);

        $user->attachRole($userRole);

        return $user;
    }

    /**
     * Handle a registration request for the application.
     *
     * @param Request $request
     * @return Response
     * @throws ValidationException
     */
    public function register(Request $request)
    {
        /** @var \Illuminate\Validation\Validator $validator */
        $validator = $this->validator($request->all());
        try {
            $validator->validate();
        } catch (ValidationException $ex) {
            // We always want to redirect to /register, even if you tried to register from modal anywhere on the side
            return redirect('/register')->withInput()->withErrors($validator->messages()->getMessages());
        }

        event(new Registered($user = $this->create($request->all())));

        $this->guard()->login($user);

        Session::flash('status', __('Registered successfully. Enjoy the website!'));

        // Set the redirect path if it was set
        $this->redirectTo = $request->get('redirect', '/profile');

        return $this->registered($request, $user) ?: redirect($this->redirectPath());
    }
}
