<?php

namespace App\Http\Controllers\Projects\PianoLit;

use Carbon\Carbon;
use App\Projects\PianoLit\{User, Piece, Api};
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class UsersController extends Controller
{
    protected $api;

    public function __construct()
    {
        $this->api = new Api;        
    }

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

    public function store(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'first_name' => 'required|string|min:2|max:160',
            'last_name' => 'required|string|min:2|max:160',
            'email' => 'required|string|email|max:160|unique:pianolit.users',
            'password' => 'required|string|min:6|confirmed',
        ]);

        if ($validator->fails()) {
            return $request->wantsJson() ? 
                        response()->json($validator->messages(), 403)
                        : redirect()->back()->with('errors', $validator->messages());
        }

        $user = User::create([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'password' => \Hash::make($request["password"]),
            'locale' => $request->locale,
            'age_range' => $request->age_range,
            'experience' => $request->experience,
            'preferred_piece_id' => $request->preferred_piece_id,
            'occupation' => $request->occupation,
            'trial_ends_at' => Carbon::now()->addWeek()
        ]);

        return $request->wantsJson() ? 
                    response()->json($user) 
                    : redirect()->back()->with('success', "The user has been successfully created!");;
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
        $pieces = Piece::orderBy('name')->get();

        $pieces->each(function($piece) {
            $this->api->setCustomAttributes($piece);
        });

        return view('projects/pianolit/users/show', compact(['user', 'pieces']));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  User  $user
     * @return \Illuminate\Http\Response
     */
    public function edit(User $user)
    {
        //
    }

    public function favorite(Request $request, User $user)
    {
        if ($user->favorites()->find($request->piece_id)) {
            $user->favorites()->detach($request->piece_id);
        } else {
            $user->favorites()->attach($request->piece_id);
        };

        return response(200);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  User  $user
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, User $user)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $user
     * @return \Illuminate\Http\Response
     */
    public function destroy(User $user)
    {
        $user->favorites()->detach();
        $user->delete();

        return redirect(route('piano-lit.users.index'))->with('success', "The user has been successfully removed!");
    }
}
