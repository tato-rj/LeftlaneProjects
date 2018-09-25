<?php

namespace App\Projects\PianoLit;

use Carbon\Carbon;

class Subscription extends PianoLit
{
	public function user()
	{
		return $this->belongsTo(User::class);
	}

	public function getLatestReceiptInfoAttribute($value)
	{
		return json_decode($value);
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
		return $this->auto_renew_status == 'true' ? Carbon::parse($this->latest_receipt_info->expires_date) : null;
	}

	public function getExpiresAtAttribute()
	{
		return $this->auto_renew_status =='false' ? Carbon::parse($this->latest_receipt_info->expires_date) : null;
	}

	public function scopeLocate($query, $subscriptionId)
	{
		return $query->where('original_transaction_id', $subscriptionId)->first();
	}

	public function handle($request)
	{
		$this->update([
			'environment' => $request->environment,

			'notification_type' => $request->notification_type,
			
			'latest_receipt' => ! empty($request->latest_receipt) 
				? $request->latest_receipt 
				: $this->latest_receipt,
			
			'latest_receipt_info' => ! empty($request->latest_receipt_info) 
				? json_encode($request->latest_receipt_info) 
				: $this->latest_receipt_info,
			
			'auto_renew_status' => ! empty($request->auto_renew_status) 
				? $request->auto_renew_status 
				: $this->auto_renew_status,
			
			'auto_renew_adam_id' => ! empty($request->auto_renew_adam_id) 
				? $request->auto_renew_adam_id 
				: $this->auto_renew_adam_id,
			
			'auto_renew_product_id' => ! empty($request->auto_renew_product_id) 
				? $request->auto_renew_product_id 
				: $this->auto_renew_product_id,
			
			'expiration_intent' => ! empty($request->expiration_intent) 
				? $request->expiration_intent 
				: $this->expiration_intent,
			
			'cancellation_date' => ! empty($request->cancellation_date) 
				? Carbon::parse($request->cancellation_date) 
				: $this->cancellation_date,
			
			'web_order_line_item_id' => ! empty($request->web_order_line_item_id) 
				? $request->web_order_line_item_id 
				: $this->web_order_line_item_id,
		]);
	}
}
