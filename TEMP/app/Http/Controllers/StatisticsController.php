<?php

namespace App\Http\Controllers;

use App\{User, UserPurchaseRecord};
use Illuminate\Http\Request;

class StatisticsController extends Controller
{
    public function index()
    {
    	$dailySignups = User::statistics()->daily()->take(30)->get();
		$monthlySignups = User::statistics()->monthly()->take(12)->get();
		$yearlySignups = User::statistics()->yearly()->take(4)->get();

		$storiesRecords = UserPurchaseRecord::whereNotIn('user_id', config('app.testersIds'))->pluck('story_id')->toArray();

		$storiesArray = array_count_values($storiesRecords);
		$storiesArray = collect($storiesArray);
		
		$topStories = $storiesArray->sort()->reverse();

		$usersRecords = UserPurchaseRecord::whereNotIn('user_id', config('app.testersIds'))->pluck('user_id')->toArray();

		$usersArray = array_count_values($usersRecords);
		$usersArray = collect($usersArray);
		
		$activeUsers = $usersArray->sort()->reverse();

    	return view('pages/statistics/index', compact(['dailySignups', 'monthlySignups', 'yearlySignups', 'topStories', 'activeUsers']));
    }
}
