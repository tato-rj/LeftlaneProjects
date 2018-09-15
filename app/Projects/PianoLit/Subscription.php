<?php

namespace App\Projects\PianoLit;

class Subscription extends PianoLit
{
	protected $casts = ['exclude_old_transactions' => 'boolean'];
	
	public function user()
	{
		return $this->belongsTo(User::class);
	}
}
