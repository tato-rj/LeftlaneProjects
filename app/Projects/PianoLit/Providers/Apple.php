<?php

namespace App\Projects\PianoLit\Providers;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7;
use Illuminate\Http\Request;
use App\Projects\PianoLit\Providers\Sandbox\Subscription as FakeSubscription;

class Apple
{
	protected $receipt_data, $password, $client, $payload;

	public function prepare(Request $request)
	{
		if (app()->environment() == 'testing')
			return $this;

		$this->setRequest();

		$this->setClient();

		$this->setPayload();

	    return $this;
	}

	public function call()
	{
		if (app()->environment() == 'testing')
			return (new FakeSubscription(now()->subMonths(1)))->generate();

		$response = $this->client->post('https://sandbox.itunes.apple.com/verifyReceipt', ['body' => $this->payload]);
	
		return json_decode($response->getBody(), true);
	}

	public function setRequest()
	{
		$this->receipt_data = $request->receipt_data;
		$this->password = $request->password;		
	}

	public function setClient()
	{
	    $this->client = new Client([
	        'headers' => ['Content-Type' => 'application/json']
	    ]);
	}

	public function setPayload()
	{
	    $this->payload = json_encode([
	        'receipt-data' => $this->receipt_data,
	        'password' => $this->password,
	        'exclude-old-transactions' => false
	    ]);
	}
}
