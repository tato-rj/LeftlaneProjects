<?php

namespace App\Projects\PianoLit\Providers\Sandbox;

use App\Projects\PianoLit\Providers\Sandbox\FakeReceipt;

class Subscription extends Sandbox
{
	use FakeReceipt;
	
	protected $receipt;

	public function generate($valid = true)
	{
		$this->receipt['in_app'] = $this->makePurchases($valid);

		$response['status'] = 0;
		$response['receipt'] = $this->receipt;
		
		return $response;
	}
}
