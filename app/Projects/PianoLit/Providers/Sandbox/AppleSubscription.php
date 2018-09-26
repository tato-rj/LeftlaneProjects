<?php

namespace App\Projects\PianoLit\Providers\Sandbox;

use App\Projects\PianoLit\Providers\Sandbox\FakeReceipt;

class AppleSubscription extends Sandbox
{
	use FakeReceipt;
	
	protected $receipt, $receipt_data, $password, $originalDate;

	public function withRequest()
	{
		$this->receipt_data = 'fake-receipt-data';

		$this->password = 'fake-password';

		return $this;
	}

	public function generate($valid = true)
	{
		$this->receipt['in_app'] = $this->makePurchases($valid);

		if (empty($this->receipt['in_app']))
			return null;

		$response['status'] = 0;
		$response['receipt'] = $this->receipt;
		
		return $response;
	}
}
