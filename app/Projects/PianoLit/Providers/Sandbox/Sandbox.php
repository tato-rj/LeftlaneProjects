<?php

namespace App\Projects\PianoLit\Providers\Sandbox;

abstract class Sandbox
{
	protected $format = 'Y-m-d h:i:s e';
	protected $originalTransactionId;

	public function __construct($date = null)
	{
		$this->originalDate = $date ?? now()->subDays(10);
		$this->originalTransactionId = '9568517712963430';
		$this->receipt = $this->receipt($this->originalDate);
	}

	public function __get($property) {
		if (property_exists($this, $property)) {
			return $this->$property;
		}
	}
}

//\Carbon\Carbon::parse($value)->format('M dS, Y \a\t h:i A e')