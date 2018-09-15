<?php

namespace Tests\Feature\PianoLit;

use Tests\PianoLitTest;
use App\Projects\PianoLit\{User, Subscription};

class SubscriptionTest extends PianoLitTest
{
	/** @test */
	public function a_user_has_a_susbcription()
	{
		$user = create(User::class);

		$this->assertNull($user->subscription);

		$subscription = create(Subscription::class);

		$user->subscription()->save($subscription);

		$this->assertInstanceOf(Subscription::class, $user->fresh()->subscription);
	}

	/** @test */
	public function a_user_can_subscribe_from_the_app()
	{
		$user = create(User::class);

		$subscription = create(Subscription::class);

		$this->post(route('piano-lit.api.subscription.handle'), [
			'user_id' => $user->id,
			'receipt_data' => $subscription->receipt_data,
			'password' => $subscription->password
		]);

		$this->assertInstanceOf(Subscription::class, $user->fresh()->subscription);
	}
}
