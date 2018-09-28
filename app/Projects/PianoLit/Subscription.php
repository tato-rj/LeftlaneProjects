<?php

namespace App\Projects\PianoLit;

use Carbon\Carbon;

class Subscription extends PianoLit
{
	protected $dates = ['renews_at'];

	public function user()
	{
		return $this->belongsTo(User::class);
	}

	public function getLatestReceiptInfoAttribute($value)
	{
		return json_decode($value);
	}

	public function getExpirationIntentTextAttribute()
	{
		$reasons = [
			null,
			'Customer canceled their subscription.', 
			'Billing error; for example customer’s payment information was no longer valid.', 
			'Customer did not agree to a recent price increase.', 
			'Product was not available for purchase at the time of renewal.', 
			'Unknown error'];

		return $reasons[$this->expiration_intent];
	}

	public function expired()
	{
		return ! now()->lte($this->renews_at);
	}

	public function scopeLocate($query, $subscriptionId)
	{
		return $query->where('original_transaction_id', $subscriptionId)->first();
	}

	public function reactivate($receipt)
	{
		$due_date = $this->user->getDueDate($receipt->product_id);

		return $this->update([
			'plan' => $receipt->product_id,
			'renews_at' => $due_date]);
	}
}
