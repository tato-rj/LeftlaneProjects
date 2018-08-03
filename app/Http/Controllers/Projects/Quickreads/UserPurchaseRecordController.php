<?php

namespace App\Http\Controllers;

use App\User;
use App\Story;
use App\UserPurchaseRecord;
use Illuminate\Http\Request;

class UserPurchaseRecordController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required',
        ]);

        if (! $request->facebook_id) {
            $user_id = null;
        } else {
            $user_id = User::where('facebook_id', $request->facebook_id)->pluck('id')[0];
        }

        $story_id = Story::where('title', '=', $request->title)->pluck('id')[0];

    	return UserPurchaseRecord::create([
    		'story_id' => $story_id,
    		'user_id' => $user_id
    	]);
    }
}
