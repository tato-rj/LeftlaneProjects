<?php

namespace App\Projects\PianoLit;

class Subscription extends PianoLit
{
	protected $dates = [
		'expires_at',
		'latest_payment_at'
	];
	
	protected $casts = ['exclude_old_transactions' => 'boolean'];
	
	public function user()
	{
		return $this->belongsTo(User::class);
	}

	public function getPurchasesAttribute()
	{
		return json_decode($this->in_app);
	}

	public function getLatestPurchaseAttribute()
	{
		return $this->purchases[0];
	}

	public function status()
	{
		return $this->latestPurchase->expires_date_ms > now()->timestamp;
	}
}
