<?php

namespace App\Projects\PianoLit\Providers\Sandbox;

abstract class Sandbox
{
	protected $format = 'Y-m-d h:i:s e';
	protected $originalTransactionId;

	public function __construct($date)
	{
		$this->originalTransactionId = $this->randomNumber(16);
		$this->originalDate = $date;
		$this->receipt = $this->receipt($date);
	}

	public function __get($property) {
		if (property_exists($this, $property)) {
			return $this->$property;
		}
	}
}
