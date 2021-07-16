<?php

namespace App\Http\Controllers\Projects\Quickreads;

use App\Projects\Quickreads\{User, Story, UserPurchaseRecord};
use Illuminate\Http\Request;

class UserPurchaseRecordController extends QuickreadsController
{
    public function show(Request $request)
    {
        return UserPurchaseRecord::where(['user_id' => $request->user_id])->get();
    }

    public function check(Request $request)
    {
        $request->validate([
            'facebook_id' => 'required|exists:quickreads.users,facebook_id',
            'title' => 'required|exists:quickreads.stories,title',
        ]);


        $user = User::where('facebook_id', $request->facebook_id)->first();
        $story = Story::where('title', $request->title)->first();

        $status = UserPurchaseRecord::where([
            'story_id' => $story->id,
            'user_id' => $user->id
        ])->exists();

        return $status ? 
            response()->json('yes') :
            response()->json('no');
    }

    public function store(Request $request)
    {
        $request->validate([
            'facebook_id' => 'required|exists:quickreads.users,facebook_id',
            'title' => 'required|exists:quickreads.stories,title',
        ]);

        // if (! $request->facebook_id) {
        //     $user_id = null;
        // } else {
        //     $user_id = User::where('facebook_id', $request->facebook_id)->pluck('id')[0];
        // }

        $user = User::where('facebook_id', $request->facebook_id)->first();
        $story = Story::where('title', $request->title)->first();

    	return UserPurchaseRecord::create([
    		'story_id' => $story->id,
    		'user_id' => $user->id
    	]);
    }

    public function delete(Request $request)
    {
        $request->validate([
            'facebook_id' => 'required|exists:quickreads.users,facebook_id',
            'title' => 'required|exists:quickreads.stories,title',
        ]);

        $user = User::where('facebook_id', $request->facebook_id)->first();
        $story = Story::where('title', $request->title)->first();

        UserPurchaseRecord::where([
            'story_id' => $story->id,
            'user_id' => $user->id
        ])->delete();

        return response(200);
    }
}
