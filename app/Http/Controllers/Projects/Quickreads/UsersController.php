<?php

namespace App\Http\Controllers\Projects\Quickreads;

use App\Projects\Quickreads\User;
use App\Projects\Quickreads\Api\V2\LegacyAccountAccess;
use App\Projects\Quickreads\Admin\{ActivityReport, ReaderReport, Queries, Editor};
use Illuminate\Http\Request;

class UsersController extends QuickreadsController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        return response()->view('projects.quickreads.users.index', ReaderReport::directory($request))
            ->header('Cache-Control', 'private, no-store');
    }

    public function app()
    {
        return LegacyAccountAccess::excludeApple(User::query())->latest()->with(['comments', 'ratings', 'stories'])->get();
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
        $existing = User::where('email', $request['email'])->first();
        if ($existing && LegacyAccountAccess::isAppleAccount($existing->id)) {
            return response()->json('Use Sign in with Apple in the current app.', 403);
        }
        $user = User::firstOrCreate(
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
        if (LegacyAccountAccess::isAppleAccount($user->id)) {
            return response()->json('Use Sign in with Apple in the current app.', 403);
        }
        return $user;
    }

    public function register(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'email' => 'email|unique:quickreads.users'
        ]);

        if ($validator->fails()) { 
            return response()->json($validator->messages()->first(), 403);
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
    public function show(Request $request, $userId)
    {
        $request->validate(['page' => 'nullable|integer|min:1|max:100000']);
        $db = Queries::db();
        $query = ReaderReport::withProvider($db->table('users as u')->select('u.id', 'u.first_name', 'u.last_name', 'u.email', 'u.locale', 'u.gender', 'u.created_at'), 'u.id');
        foreach (['user_purchase_records' => 'saves', 'ratings' => 'ratings', 'comments' => 'comments'] as $table => $key) {
            $query->selectSub($db->table($table.' as related')->selectRaw('COUNT(*)')->whereColumn('related.user_id', 'u.id'), $key);
        }
        $user = $query->where('u.id', $userId)->first();
        abort_unless($user, 404);
        ReaderReport::decorate(collect([$user]));
        $apple = $db->table('apple_identities')->where('user_id', $userId)
            ->selectRaw('COUNT(*) as identities, SUM(CASE WHEN revoked_at IS NULL THEN 1 ELSE 0 END) as active, MAX(authorized_at) as authorized_at')->first();
        $currentStories = ActivityReport::details(ActivityReport::base()->where('activity.reader_kind', 'account')->where('activity.user_id', $userId))->limit(10)->get();
        $counts = ['saved stories' => $user->saves, 'ratings' => $user->ratings, 'comments' => $user->comments];
        $saves = $db->table('user_purchase_records as p')->leftJoin('stories as s', 's.id', '=', 'p.story_id')
            ->select('p.id', 'p.created_at', 's.slug', 's.title')->where('p.user_id', $userId)->orderByDesc('p.created_at')->orderByDesc('p.id')->paginate(25)->withQueryString();
        $ratings = $db->table('ratings as r')->leftJoin('stories as s', 's.id', '=', 'r.story_id')
            ->select('s.title', 'r.score', 'r.created_at')->where('r.user_id', $userId)->orderByDesc('r.created_at')->orderByDesc('r.id')->limit(10)->get();
        $comments = $db->table('comments as c')->leftJoin('stories as s', 's.id', '=', 'c.story_id')
            ->select('s.title', 'c.body', 'c.created_at')->where('c.user_id', $userId)->orderByDesc('c.created_at')->orderByDesc('c.id')->limit(10)->get();
        return response()->view('projects.quickreads.users.show', compact('user', 'apple', 'currentStories', 'counts', 'saves', 'ratings', 'comments'))->header('Cache-Control', 'private, no-store');
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
     * @param  int  $user
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, User $user)
    {
        return $this->destroyById($request, $user->id);
    }

    public function destroyById(Request $request, $userId)
    {
        $request->validate(['confirm' => 'required|accepted']);
        if (\Illuminate\Support\Facades\Schema::connection('quickreads')->hasTable('story_activity')) {
            try {
                app(\App\Projects\Quickreads\Api\V2\AccountDeletion::class)->delete((int) $userId,
                    app(\App\Projects\Quickreads\Api\V2\AppleProvider::class), 404);
            } catch (\App\Projects\Quickreads\Api\V2\ApiException $e) {
                return back()->withErrors(['delete' => 'Account deletion could not be completed. Please retry later.']);
            }
            Editor::changed();
            return redirect('/quickreads/users')->with('success', 'User and their saved stories, ratings, comments and access tokens removed.');
        }
        $db = Queries::db();
        $db->transaction(function () use ($db, $userId) {
            $user = $db->table('users')->where('id', $userId)->lockForUpdate()->first();
            abort_unless($user, 404);
            foreach (['user_purchase_records', 'ratings', 'comments'] as $table) $db->table($table)->where('user_id', $userId)->delete();
            $db->table('api_v2_tokens')->where('tokenable_id', $userId)->where('tokenable_type', \App\Projects\Quickreads\Api\V2\User::class)->delete();
            $db->table('password_resets')->where('email', $user->email)->delete();
            $db->table('users')->where('id', $userId)->delete();
        });
        Editor::changed();
        return redirect('/quickreads/users')->with('success', 'User and their saved stories, ratings, comments and access tokens removed.');
    }
}
