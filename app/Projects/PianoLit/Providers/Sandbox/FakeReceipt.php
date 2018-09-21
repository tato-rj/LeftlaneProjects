<?php

namespace App\Projects\PianoLit\Providers\Sandbox;

use Carbon\Carbon;

trait FakeReceipt
{
	public function receipt($date)
	{
		$date->addDays(rand(1,7));

		return [
			"version_external_identifier" => 0,
			"request_date" => $date->setTimezone('Etc/GMT')->addSecond()->format($this->format),
			"request_date_pst" => $date->setTimezone('America/Los_Angeles')->addSecond()->format($this->format),
			"request_date_ms" => $date->setTimezone('Etc/GMT')->addSecond()->timestamp,
			"receipt_type" => "ProductionSandbox",
			"bundle_id" => "com.leftlaneapps.PianoLIT",
			"receipt_creation_date" => $date->setTimezone('Etc/GMT')->format($this->format),
			"receipt_creation_date_pst" => $date->setTimezone('America/Los_Angeles')->format($this->format),
			"receipt_creation_date_ms" => $date->setTimezone('Etc/GMT')->timestamp,
			"download_id" => 0,
			"adam_id" => 0,
			"app_item_id" => 0,
			"application_version" => "1",
			"original_purchase_date" => "2013-08-01 07:00:00 Etc\/GMT",
			"original_purchase_date_pst" => "2013-08-01 00:00:00 America\/Los_Angeles",
			"original_purchase_date_ms" => "1375340400000",
			"original_application_version" => "1.0",
			"cancellation_date" => null,
			"cancellation_reason" => null,
		];
	}

	public function purchase($date)
	{
		$oneMonthAfter = $date->copy()->addMonth();

		return [
			"quantity" => 1,
			"product_id" => 'monthly',
			"transaction_id" => $this->randomNumber(16),
			"purchase_date" => $date->setTimezone('Etc/GMT')->format($this->format),
			"purchase_date_pst" => $date->setTimezone('America/Los_Angeles')->format($this->format),
			"purchase_date_ms" => $date->setTimezone('Etc/GMT')->timestamp,
			"original_purchase_date" => $this->receipt['receipt_creation_date'],
			"original_purchase_date_pst" => $this->receipt['receipt_creation_date_pst'],
			"original_purchase_date_ms" => $this->receipt['receipt_creation_date_ms'],
			"expires_date" => $oneMonthAfter->setTimezone('Etc/GMT')->format($this->format),
			"expires_date_pst" => $oneMonthAfter->setTimezone('America/Los_Angeles')->format($this->format),
			"expires_date_ms" => $oneMonthAfter->setTimezone('Etc/GMT')->timestamp,
			"expiration_intent" => null,
			"is_in_billing_retry_period" => null,
			"web_order_line_item_id" => $this->randomNumber(16),
			"auto_renew_status" => 1,
			"price_consent_status" => 1,
			"original_transaction_id" => $this->originalTransactionId,
			"is_in_intro_offer_period" => false,
			"is_trial_period" => false,			
		];
	}

	public function makePurchases()
	{
		$array = [];

		$receiptDate = Carbon::parse($this->receipt['receipt_creation_date']);

		while ($receiptDate->lt(now())) {
		    array_push($array, $this->purchase($receiptDate));
		    $receiptDate->addMonth();
		}

		return $array;
	}

	public function randomNumber($length) {
		$result = '';

		for($i = 0; $i < $length; $i++) {
			$result .= mt_rand(0, 9);
		}

		return $result;
	}
}
