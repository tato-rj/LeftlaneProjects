<?php

namespace Tests\Feature\PianoLit;

use Tests\PianoLitTest;
use App\Projects\PianoLit\{User, Subscription, Admin};
use App\Projects\PianoLit\Providers\Sandbox\Subscription as FakeSubscription;
use Carbon\Carbon;

class SubscriptionTest extends PianoLitTest
{
	/** @test */
	public function an_admin_can_extend_the_users_trial()
	{
		$admin = create(Admin::class, ['role' => 'manager']);

		$this->signIn($admin);

		$user = create(User::class);

		$oldTrialDate = $user->trial_ends_at;

		$this->post(route('piano-lit.users.extend-trial', $user->id));

		$this->assertTrue($oldTrialDate->lt($user->fresh()->trial_ends_at));
	}

	/** @test */
	public function a_user_can_subscribe()
	{
		$user = create(User::class);

		$this->post(route('piano-lit.api.subscription.handle'), [
			'user_id' => $user->id
		]);

		$this->assertInstanceOf(Subscription::class, $user->fresh()->subscription);
	}

	/** @test */
	public function the_same_user_cannot_subscribe_twice()
	{
		$user = create(User::class);

		$this->post(route('piano-lit.api.subscription.handle'), [
			'user_id' => $user->id
		]);

		$this->post(route('piano-lit.api.subscription.handle'), [
			'user_id' => $user->id
		]);

		$this->assertEquals(1, Subscription::where('user_id', $user->id)->count());
	}

	/** @test */
	public function a_user_knows_when_the_subscription_is_set_to_renew()
	{
		$user = create(User::class);

		$receipt = (new FakeSubscription(now()->subMonths(2)))->generate();

		$user->subscribe($receipt['receipt']);

		$this->assertInstanceOf(Carbon::class, $user->subscription->renews_at);		 
	}
}
