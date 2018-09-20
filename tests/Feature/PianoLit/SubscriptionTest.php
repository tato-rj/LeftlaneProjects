<?php

namespace Tests\Feature\PianoLit;

use Tests\PianoLitTest;
use App\Projects\PianoLit\{User, Subscription};
use App\Projects\PianoLit\Providers\Sandbox\Subscription as FakeSubscription;

class SubscriptionTest extends PianoLitTest
{
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
	public function a_user_knows_if_the_subscription_is_expired()
	{
		$activeUser = create(User::class);

		$validReceipt = (new FakeSubscription(now()->subMonths(1)))->generate($valid = true);

		$activeUser->subscribe($validReceipt['receipt']);

		$this->assertTrue($activeUser->subscription->status());

		$inactiveUser = create(User::class);

		$expiredReceipt = (new FakeSubscription(now()->subMonths(1)))->generate($valid = false);

		$inactiveUser->subscribe($expiredReceipt['receipt']);

		$this->assertFalse($inactiveUser->subscription->status());
	}
}
