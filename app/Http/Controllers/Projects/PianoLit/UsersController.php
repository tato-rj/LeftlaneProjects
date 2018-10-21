<?php

namespace App\Http\Controllers\Projects\PianoLit;

use Carbon\Carbon;
use App\Projects\PianoLit\{User, Piece, Api};
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

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
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(User $user)
    {
        return $pieces;
        if (request('format') == 'json')
            return $user->subscription;

        $pieces = Piece::orderBy('name')->get();

        $pieces->each(function($piece) use ($user) {
            $this->api->setCustomAttributes($piece, $user->id);
        });

        return view('projects/pianolit/users/show/index', compact(['user', 'pieces']));
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
            'first_name' => 'required',
            'last_name' => 'required',
            'email' => 'required|email|unique:pianolit.users',
            'password' => 'required|string|min:6|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->messages(), 403);
        }

        $user = User::create([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'password' => \Hash::make($request->password),
            'locale' => $request->locale,
            'age_range' => strtolower($request->age_range),
            'experience' => strtolower($request->experience),
            'preferred_piece_id' => $request->preferred_piece_id,
            'occupation' => strtolower($request->occupation),
            'trial_ends_at' => Carbon::now()->addWeek()
        ]);

        \Mail::to($user->email)->send(new \App\Mail\PianoLit\WelcomeEmail($user));

        if ($request->has('from_backend'))
            return redirect()->back()->with('success', "The user has been successfully created!");

        return $user;
    }

    public function appLogin(Request $request)
    {
        $user = User::where('email', $request->email)->first();

        if ($user) {
            if (\Hash::check($request->password, $user->password))
                return response()->json($user);

            $error = ['Sorry, the password is not valid.'];

        } else {
            $error = ['This email is not registered.'];
        }

        $response = new \stdClass;
        $response->error = $error;

        return response()->json($response);
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

    public function setFavorite(Request $request)
    {
        $user = User::find($request->user_id);

        if (! $user)
            return response()->json(['Sorry, user not found.']);

        if ($user->favorites()->find($request->piece_id)) {
            $user->favorites()->detach($request->piece_id);
        } else {
            $user->favorites()->attach($request->piece_id);
        };

        return response()->json(['Add to favorites!']);
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
        $user->subscription()->delete();
        $user->delete();

        return redirect(route('piano-lit.users.index'))->with('success', "The user has been successfully removed!");
    }
}
