<?php

namespace App\Http\Controllers\Auth\VideoUploader;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class LoginController extends Controller
{
	protected $defaultRedirectTo = '/office';

    public function __construct()
    {
        $this->middleware('guest:videouploader-admin');
    }

	public function showLoginForm()
	{
		return view('projects/videouploader/auth/login');
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
// return \App\Projects\VideoUploader\Admin::all();
// return \App\Projects\VideoUploader\Admin::first()->update([
// 	'password' => '$2y$10$3O.VdXI80tmCg7Sr/9KKWeOPoTc29HxoKycn9.J3lEP7bFsIicXkW'
// ]);
		if (\Auth::guard('videouploader-admin')->attempt($credentials, true)) {
			return redirect()->intended('videouploader');
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
        return redirect()->to(route('videouploader.login'))
            ->withInput($request->only('email', 'remember'))
            ->withErrors([
                'email' => \Lang::get('auth.failed'),
            ]);
    }
}
