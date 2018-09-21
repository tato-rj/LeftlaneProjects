<?php

use Illuminate\Database\Seeder;
use App\Projects\PianoLit\User;
use App\Projects\PianoLit\Providers\Sandbox\Subscription as FakeSubscription;

class SubscriptionsTableSeeder extends Seeder
{
	public function run()
	{
		$valid = [true, false];

		$users = User::take(mt_rand(25, 30))->get();

		foreach ($users as $user) {
			$receipt = (new FakeSubscription($user->created_at))->generate();

			$user->subscribe($receipt['receipt']);
		}
	}
}
