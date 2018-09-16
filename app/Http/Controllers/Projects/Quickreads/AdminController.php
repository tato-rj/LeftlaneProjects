<?php

namespace App\Http\Controllers\Projects\Quickreads;

use Illuminate\Http\Request;
use App\Projects\Quickreads\{Story, Author, Category, QuickreadsUser, UserPurchaseRecord, Subscription};

class AdminController extends QuickreadsController
{
	public function index()
	{
		$stories_count = Story::count();
		$categories_count = Category::count();
		$authors_count = Author::count();
		$users_count = QuickreadsUser::count();
		$subscriptions_count = Subscription::count();

		return view('projects/quickreads/dashboard', 
			compact('stories_count', 'categories_count', 'authors_count', 'users_count', 'subscriptions_count'));
	}

    public function statistics()
    {

    	$dailySignups = QuickreadsUser::statistics()->daily()->take(30)->get();
		$monthlySignups = QuickreadsUser::statistics()->monthly()->take(12)->get();
		$yearlySignups = QuickreadsUser::statistics()->yearly()->take(4)->get();

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
