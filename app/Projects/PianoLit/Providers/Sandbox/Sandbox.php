<?php

namespace App\Projects\PianoLit\Providers\Sandbox;

abstract class Sandbox
{
	protected $format = 'Y-m-d h:i:s e';
	protected $originalTransactionId;

	public function __construct($date = null)
	{
		$this->originalDate = $date ?? now()->subDays(10);
		$this->originalTransactionId = $this->randomNumber(16);
		$this->receipt = $this->receipt($this->originalDate);
	}

	public function __get($property) {
		if (property_exists($this, $property)) {
			return $this->$property;
		}
	}
}
