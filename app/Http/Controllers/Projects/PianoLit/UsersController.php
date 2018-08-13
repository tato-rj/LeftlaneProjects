<?php

namespace App\Http\Controllers\Projects\PianoLit;

use App\Projects\PianoLit\User;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class UsersController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $users = User::latest()->paginate(10);

        return view('projects/pianolit/users/index', compact('users'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        return User::firstOrCreate(
            [
                'email' => $request["email"],
            ],
            [
            'slug' => str_slug("{$request["first_name"]} {$request["last_name"]}"),
            'first_name' => $request["first_name"],
            'last_name' => $request["last_name"],
            'locale' => $request["locale"],
            'gender' => $request["gender"],
            'facebook_id' => $request["facebook_id"],
        ]);
    }

    public function register(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'email' => 'unique:pianolit.users'
        ]);

        if ($validator->fails()) { 
            return response()->json($validator->messages(), 403);
        }

        $user = User::create([
            'slug' => str_slug("{$request["first_name"]} {$request["last_name"]}"),
            'first_name' => $request["first_name"],
            'last_name' => $request["last_name"],
            'email' => $request["email"],
            'facebook_id' => \Hash::make(\Carbon\Carbon::now()->timestamp.$request["email"]),
            'password' => \Hash::make($request["password"]),
            'locale' => $request["locale"]
        ]);

        return response()->json($user);
    }

    public function appLogin($email, $password)
    {
        $user = User::where('email', $email)->first();

        if ($user) {
            if (\Hash::check($password, $user->password))
                return response()->json($user);            
        }

        return response()->json();
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(User $user)
    {
        return view('projects/pianolit/users/show', compact('user'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
