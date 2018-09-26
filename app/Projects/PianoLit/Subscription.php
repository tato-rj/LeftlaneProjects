<?php

namespace App\Projects\PianoLit;

use Carbon\Carbon;

class Subscription extends PianoLit
{
	protected $casts = ['auto_renew_status' => 'boolean'];

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
		return $this->latest_receipt_info->expires_date_ms < now()->timestamp;
	}

	public function cancelled()
	{
		return $this->cancellation_date ?? false;
	}

	public function getCancellationDateAttribute($date)
	{
		return $date ? Carbon::parse($date) : null;
	}

	public function getRenewsAtAttribute()
	{
		return $this->auto_renew_status ? Carbon::parse($this->latest_receipt_info->expires_date) : null;
	}

	public function getExpiresAtAttribute()
	{
		return ! $this->auto_renew_status ? Carbon::parse($this->latest_receipt_info->expires_date) : null;
	}

	public function scopeLocate($query, $subscriptionId)
	{
		return $query->where('original_transaction_id', $subscriptionId)->first();
	}

	public function handle($request)
	{
		$this->update([
			'environment' => $request->environment,

			'notification_type' => strtolower($request->notification_type),
			
			'latest_receipt' => ! is_null($request->latest_receipt) 
				? $request->latest_receipt 
				: $request->latest_expired_receipt,
			
			'latest_receipt_info' => ! is_null($request->latest_receipt_info) 
				? $request->latest_receipt_info 
				: $request->latest_expired_receipt_info,
			
			'auto_renew_status' => ! is_null($request->auto_renew_status) 
				? $request->auto_renew_status 
				: $this->auto_renew_status,
			
			'auto_renew_adam_id' => ! is_null($request->auto_renew_adam_id) 
				? $request->auto_renew_adam_id 
				: $this->auto_renew_adam_id,
			
			'auto_renew_product_id' => ! is_null($request->auto_renew_product_id) 
				? $request->auto_renew_product_id 
				: $this->auto_renew_product_id,
			
			'expiration_intent' => ! is_null($request->expiration_intent) 
				? $request->expiration_intent 
				: $this->expiration_intent,
			
			'cancellation_date' => ! is_null($request->cancellation_date) 
				? Carbon::parse($request->cancellation_date) 
				: null,
			
			'web_order_line_item_id' => ! is_null($request->web_order_line_item_id) 
				? $request->web_order_line_item_id 
				: $this->web_order_line_item_id,
		]);
	}
}
