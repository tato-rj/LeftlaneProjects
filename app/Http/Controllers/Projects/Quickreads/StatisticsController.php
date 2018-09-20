<?php

namespace App\Http\Controllers\Projects\Quickreads;

use App\Projects\Quickreads\{QuickreadsUser, UserPurchaseRecord};
use Illuminate\Http\Request;

class StatisticsController extends QuickreadsController
{
    public function index()
    {
    	$dailySignups = QuickreadsUser::statistics()->daily()->take(30)->get();
		$monthlySignups = QuickreadsUser::statistics()->monthly()->take(12)->get();
		$yearlySignups = QuickreadsUser::statistics()->yearly()->take(4)->get();

		$storiesRecords = UserPurchaseRecord::whereNotIn('user_id', config('app.testersIds'))->pluck('story_id')->toArray();

		$storiesArray = array_count_values($storiesRecords);
		$storiesArray = collect($storiesArray);
		
		$topStories = $storiesArray->sort()->reverse();

		$usersRecords = UserPurchaseRecord::whereNotIn('user_id', config('app.testersIds'))->pluck('user_id')->toArray();

		$usersArray = array_count_values($usersRecords);
		$usersArray = collect($usersArray);
		
		$activeUsers = $usersArray->sort()->reverse();

    	return view('projects/quickreads/statistics/index', compact(['dailySignups', 'monthlySignups', 'yearlySignups', 'topStories', 'activeUsers']));
    }
}
