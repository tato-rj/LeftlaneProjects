<?php

namespace App\Http\Controllers\Projects\PianoLit;

use Illuminate\Http\Request;
use App\Projects\PianoLit\{Composer, Piece, Tag, User};
use App\Http\Controllers\Controller;

class AdminController extends Controller
{
	public function index()
	{
		$pieces_count = Piece::count();
		$tags_count = Tag::count();
		$composers_count = Composer::count();
		$users_count = User::count();

		return view('projects/pianolit/dashboard', 
			compact('pieces_count', 'tags_count', 'composers_count', 'users_count'));
	}

    public function statistics()
    {
    	$dailySignups = User::statistics()->daily()->take(30)->get();
		$monthlySignups = User::statistics()->monthly()->take(12)->get();
		$yearlySignups = User::statistics()->yearly()->take(4)->get();

		$storiesRecords = UserPurchaseRecord::pluck('story_id')->toArray();

		$storiesArray = array_count_values($storiesRecords);
		$storiesArray = collect($storiesArray);
		
		$topStories = $storiesArray->sort()->reverse();

		$usersRecords = UserPurchaseRecord::pluck('user_id')->toArray();

		$usersArray = array_count_values($usersRecords);
		$usersArray = collect($usersArray);
		
		$activeUsers = $usersArray->sort()->reverse();

    	return view('projects/quickreads/statistics/index', 
    		compact(['dailySignups', 'monthlySignups', 'yearlySignups', 'topStories', 'activeUsers']));
    }
}
