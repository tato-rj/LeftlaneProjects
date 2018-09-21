<?php

namespace App\Projects\PianoLit;

use Carbon\Carbon;

class Subscription extends PianoLit
{
	protected $casts = ['exclude_old_transactions' => 'boolean'];
	protected $appends = ['purchases'];
	
	public function user()
	{
		return $this->belongsTo(User::class);
	}

	public function getPurchasesAttribute()
	{
		return collect(json_decode($this->in_app))->sortByDesc('purchase_date');
	}

	public function getLatestPurchaseAttribute()
	{
		return $this->purchases->first();
	}

	public function status()
	{
		return $this->latestPurchase->expires_date_ms > now()->timestamp;
	}

	public function getReceiptCreationDateAttribute($date)
	{
		return Carbon::parse($date);
	}

	public function getRequestDateAttribute($date)
	{
		return Carbon::parse($date);
	}

	public function getCancellationDateAttribute($date)
	{
		return $date ? Carbon::parse($date) : null;
	}

	public function getRenewsAtAttribute()
	{
		return Carbon::parse($this->latest_purchase->expires_date);
	}
}
