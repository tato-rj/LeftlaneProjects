<?php

namespace Tests\Feature\PianoLit;

use Tests\PianoLitTest;
use App\Projects\PianoLit\{User, Subscription, Admin};
use App\Projects\PianoLit\Providers\Sandbox\AppleSubscription;
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

		$this->post(route('piano-lit.users.update-trial', $user->id));

		$this->assertTrue($oldTrialDate->lt($user->fresh()->trial_ends_at));
	}

	/** @test */
	public function an_admin_can_restart_a_users_trial()
	{
		$admin = create(Admin::class, ['role' => 'manager']);

		$this->signIn($admin);

		$user = create(User::class, [
			'created_at' => now()->subWeeks(4),
			'trial_ends_at' => now()->subWeeks(3)
		]);

		$this->assertEquals('expired', $user->getStatus());

		$this->post(route('piano-lit.users.update-trial', $user->id));

		$this->assertEquals('trial', $user->fresh()->getStatus());	 
	}

	/** @test */
	public function a_user_can_subscribe()
	{
		$subscription = new AppleSubscription;

		$user = create(User::class);

		$this->assertFalse($user->subscription()->exists());

		$this->postSubscription($user, $subscription);

		$this->assertTrue($user->subscription()->exists());

		$this->assertEquals('active', $user->getStatus());
	}

	/** @test */
	public function the_same_user_cannot_subscribe_twice()
	{
		$this->expectException('Illuminate\Auth\Access\AuthorizationException');

		$subscription = new AppleSubscription;

		$user = create(User::class);

		$this->postSubscription($user, $subscription);

		$this->postSubscription($user, $subscription);

		$this->assertEquals(1, Subscription::where('user_id', $user->id)->count());
	}

	/** @test */
	public function the_app_can_check_the_status_of_a_subscription()
	{
		$subscription = new AppleSubscription;

		$user = create(User::class);

		$this->postSubscription($user, $subscription);

		$status = $this->get(route('piano-lit.api.subscription.status', ['user_id' => $user->id]));

		$this->assertTrue(json_decode($status->content()));
	}

	/** @test */
	public function a_subscription_is_reactivated_if_the_due_date_has_passed_but_it_has_been_renewed()
	{
		$subscription = new AppleSubscription;

		$user = create(User::class);

		$this->postSubscription($user, $subscription);

		$user->subscription->update(['renews_at' => now()->subDay()]);

		$this->get(route('piano-lit.api.subscription.status', ['user_id' => $user->id]));

		$this->assertTrue(now()->lt($user->subscription->fresh()->renews_at));
	}
}
