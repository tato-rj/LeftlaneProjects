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

		$this->assertEquals('expired', $user->status());

		$this->post(route('piano-lit.users.update-trial', $user->id));

		$this->assertEquals('trial', $user->fresh()->status());	 
	}

	/** @test */
	public function a_user_can_subscribe()
	{
		$subscription = new AppleSubscription;

		$user = create(User::class);

		$this->assertFalse($user->subscription()->exists());

		$this->post(route('piano-lit.api.subscription.create'), [
			'user_id' => $user->id,
			'receipt_data' => $subscription->withRequest()->receipt_data,
			'password' => $subscription->withRequest()->password
		]);

		$this->assertTrue($user->subscription()->exists());
	}

	/** @test */
	public function the_same_user_cannot_subscribe_twice()
	{
		$this->expectException('Illuminate\Auth\Access\AuthorizationException');

		$subscription = new AppleSubscription;

		$user = create(User::class);

		$this->post(route('piano-lit.api.subscription.create'), [
			'user_id' => $user->id,
			'receipt_data' => $subscription->withRequest()->receipt_data,
			'password' => $subscription->withRequest()->password
		]);

		$this->post(route('piano-lit.api.subscription.create'), [
			'user_id' => $user->id,
			'receipt_data' => $subscription->withRequest()->receipt_data,
			'password' => $subscription->withRequest()->password
		]);

		$this->assertEquals(1, Subscription::where('user_id', $user->id)->count());
	}

	/** @test */
	public function a_users_status_is_set_to_pending_until_apples_notification_confirms_a_new_subscription()
	{
		$subscription = new AppleSubscription;

		$user = create(User::class);

		$this->assertFalse($user->subscription()->exists());

		$this->assertEquals('trial', $user->status());

		$this->postSubscription($user, $subscription);

		$this->assertEquals('pending', $user->status());
	}

	/** @test */
	public function a_subscription_receives_notifications_on_initial_buy()
	{
		$subscription = new AppleSubscription;

		$user = create(User::class);

		$this->postSubscription($user, $subscription);

		$this->assertEquals('pending', $user->status());

		$this->post(route('piano-lit.api.subscription.update'), [
			'notification_type' => 'initial_buy',
			'original_transaction_id' => $user->subscription->original_transaction_id,
			'environment' => 'Sandbox',
			'latest_receipt' => $user->subscription->latest_receipt,
			'latest_receipt_info' => json_encode($user->subscription->latest_receipt_info),
			'auto_renew_status' => 1,
			'auto_renew_adam_id' => 'appleId',
			'auto_renew_product_id' => 'productId',
			'expiration_intent' => null
		]);

		$this->assertEquals('active', $user->fresh()->status());
	}

	/** @test */
	public function a_subscription_receives_notifications_on_renewal_and_interactive_renewal()
	{
		$subscription = new AppleSubscription;

		$user = create(User::class);

		$this->postSubscription($user, $subscription);

		$this->assertEquals('pending', $user->status());

		$this->post(route('piano-lit.api.subscription.update'), [
			'notification_type' => 'renewal',
			'original_transaction_id' => $user->subscription->original_transaction_id,
			'environment' => 'Sandbox',
			'latest_receipt' => $user->subscription->latest_receipt,
			'latest_receipt_info' => json_encode($user->subscription->latest_receipt_info),
			'auto_renew_status' => 1,
			'auto_renew_adam_id' => 'appleId',
			'auto_renew_product_id' => 'productId',
			'expiration_intent' => null
		]);

		$this->assertEquals('active', $user->fresh()->status());
	}

	/** @test */
	public function a_subscription_receives_notifications_on_cancel()
	{
		$subscription = new AppleSubscription;

		$user = create(User::class);

		$this->postSubscription($user, $subscription);

		$this->assertEquals('pending', $user->status());

		$expiredReceipt = $user->subscription->latest_receipt_info;
		$expiredReceipt->expires_date = now()->format('Y-m-d h:i:s e');
		$expiredReceipt->expires_date_pst = now()->timezone('America/Los_Angeles')->format('Y-m-d h:i:s e');
		$expiredReceipt->expires_date_ms = now()->timestamp;

		$this->post(route('piano-lit.api.subscription.update'), [
			'notification_type' => 'cancel',
			'cancellation_date' => now()->format('Y-m-d h:i:s e'),
			'web_order_line_item_id' => '1000000040495394',
			'original_transaction_id' => $user->subscription->original_transaction_id,
			'environment' => 'Sandbox',
			'latest_receipt' => $user->subscription->latest_receipt,
			'latest_receipt_info' => json_encode($user->subscription->latest_receipt_info),
			'latest_expired_receipt' => $user->subscription->latest_receipt,
			'latest_expired_receipt_info' => $expiredReceipt,
			'auto_renew_status' => 0,
			'auto_renew_adam_id' => 'appleId',
			'auto_renew_product_id' => 'productId',
			'expiration_intent' => '0'
		]);

		$this->assertEquals('inactive', $user->fresh()->status());
	}

	/** @test */
	public function a_subscription_receives_notifications_on_did_change_renewal_pref()
	{
		$subscription = new AppleSubscription;

		$user = create(User::class);

		$this->postSubscription($user, $subscription);

		$this->assertEquals('pending', $user->status());

		$this->post(route('piano-lit.api.subscription.update'), [
			'notification_type' => 'cancel',
			'original_transaction_id' => $user->subscription->original_transaction_id,
			'environment' => 'Sandbox',
			'latest_receipt' => $user->subscription->latest_receipt,
			'latest_receipt_info' => json_encode($user->subscription->latest_receipt_info),
			'auto_renew_status' => 0,
			'auto_renew_adam_id' => 'appleId',
			'auto_renew_product_id' => 'productId',
		]);

		$this->assertEquals('active', $user->fresh()->status());

		$this->assertNull($user->subscription->fresh()->renews_at);

		$this->assertNotNull($user->subscription->fresh()->expires_at);
	}
}
