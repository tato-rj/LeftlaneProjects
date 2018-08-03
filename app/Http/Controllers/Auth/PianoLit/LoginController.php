<?php

namespace App\Http\Controllers\Auth\PianoLit;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class LoginController extends Controller
{
	protected $defaultRedirectTo = '/office';

    public function __construct()
    {
        $this->middleware('guest:pianolit-admin');
    }

	public function showLoginForm()
	{
		return view('projects/pianolit/auth/login');
	}

	public function login(Request $request)
	{
		$validator = \Validator::make($request->all(), [
				'email' => 'required|email',
				'password' => 'required'
			]);

        if ($validator->fails()) {
            return back()
	            ->withInput($request->only('email', 'remember'))
	            ->withErrors($validator);
        }

		$credentials = [
			'email' => $request->email,
			'password' => $request->password
		];

		if (\Auth::guard('pianolit-admin')->attempt($credentials, $request->remember)) {
			return redirect()->intended('/office');
		}

		return $this->sendFailedLoginResponse($request);
		// return back()->withInput($request->only('email', 'remember'));
	}

    /**
     * Get the failed login response instance.
     *
     * @param \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    protected function sendFailedLoginResponse(Request $request)
    {
        return redirect()->to(route('pianolit.admin.login'))
            ->withInput($request->only('email', 'remember'))
            ->withErrors([
                'email' => \Lang::get('auth.failed'),
            ]);
    }
}
