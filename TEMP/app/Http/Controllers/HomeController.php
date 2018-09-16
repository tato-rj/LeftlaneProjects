<?php

namespace App\Http\Controllers;

use App\{Story, Author, Category, User, Subscription};
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth', ['except' => ['leftlane']]);
    }

	public function leftlane()
	{
		return view('leftlane/page');
	}

	public function admin()
	{
		$stories_count = Story::count();
		$categories_count = Category::count();
		$authors_count = Author::count();
		$users_count = User::count();
		$subscriptions_count = Subscription::count();

		return view('pages/dashboard', compact('stories_count', 'categories_count', 'authors_count', 'users_count', 'subscriptions_count'));
	}
}
